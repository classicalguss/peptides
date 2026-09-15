<?php

namespace Tests\Feature;

use App\Payments\VerifiedCryptoSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\Transaction;
use Tests\TestCase;

class VerifiedCryptoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);

        config([
            'verified-crypto.enabled' => true,
            'verified-crypto.webhook_secret' => self::SECRET,
        ]);
    }

    private function order(array $attributes = []): Order
    {
        return Order::factory()->create(array_merge([
            'status' => 'awaiting-payment',
            'placed_at' => null,
            'total' => 12550,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendCallback(array $payload, ?string $secret = null, ?string $timestamp = null): TestResponse
    {
        $body = json_encode($payload);
        $timestamp ??= (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? self::SECRET);

        return $this->call(
            'POST',
            route('webhooks.verified-crypto'),
            [], [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_VCC_TIMESTAMP' => $timestamp,
                'HTTP_X_VCC_SIGNATURE' => $signature,
            ],
            $body,
        );
    }

    public function test_a_signed_settlement_callback_marks_the_order_paid_and_records_the_tx_hash(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
            'session_id' => 'sess_test',
        ])->assertOk()->assertJson(['ok' => true, 'handled' => true]);

        $order->refresh();

        $this->assertSame('payment-received', $order->status);
        $this->assertNotNull($order->placed_at);
        $this->assertSame('0xabc123', $order->meta['verified_crypto']['tx_hash']);

        $transaction = Transaction::where('order_id', $order->id)->sole();

        $this->assertTrue((bool) $transaction->success);
        $this->assertSame('verified-crypto', $transaction->driver);
        $this->assertSame('0xabc123', $transaction->reference);
        $this->assertSame(12550, $transaction->amount->value);
    }

    public function test_it_maps_the_relay_txid_out_field_to_the_transaction_reference(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'completed',
            'txid_out' => '0xdeadbeef',
        ])->assertOk();

        $this->assertSame('0xdeadbeef', Transaction::where('order_id', $order->id)->sole()->reference);
    }

    public function test_a_repeated_callback_does_not_create_a_second_transaction(): void
    {
        $order = $this->order();

        $payload = [
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ];

        $this->sendCallback($payload)->assertOk();
        $placedAt = $order->fresh()->placed_at;

        $this->sendCallback($payload)->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(1, Transaction::where('order_id', $order->id)->count());
        $this->assertEquals($placedAt, $order->fresh()->placed_at);
    }

    /**
     * Section 6 of the API guide: lowercase hex HMAC-SHA256 over
     * "{timestamp}.{raw body}". Only that construction is accepted.
     */
    public function test_a_signature_in_any_other_construction_is_rejected(): void
    {
        $order = $this->order();
        $body = json_encode(['order_id' => $order->reference, 'event' => 'payment.confirmed', 'tx_hash' => '0xfmt']);
        $ts = (string) now()->timestamp;

        foreach ([
            hash_hmac('sha256', $body, self::SECRET),                              // body only
            hash_hmac('sha256', $ts.$body, self::SECRET),                          // no dot
            base64_encode(hash_hmac('sha256', $ts.'.'.$body, self::SECRET, true)), // base64
        ] as $wrong) {
            $this->call('POST', route('webhooks.verified-crypto'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_VCC_TIMESTAMP' => $ts,
                'HTTP_X_VCC_SIGNATURE' => $wrong,
            ], $body)->assertUnauthorized();
        }

        $this->assertSame('awaiting-payment', $order->fresh()->status);
    }

    public function test_a_callback_with_a_bad_signature_is_rejected_and_the_order_is_untouched(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ], secret: 'wrong-secret')->assertUnauthorized();

        $this->assertSame('awaiting-payment', $order->fresh()->status);
        $this->assertNull($order->fresh()->placed_at);
        $this->assertSame(0, Transaction::count());
    }

    public function test_an_unsigned_callback_is_rejected(): void
    {
        $order = $this->order();

        $this->postJson(route('webhooks.verified-crypto'), [
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ])->assertUnauthorized();

        $this->assertSame(0, Transaction::count());
    }

    public function test_a_stale_timestamp_is_rejected_as_a_replay(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ], timestamp: (string) now()->subHour()->timestamp)->assertUnauthorized();

        $this->assertSame(0, Transaction::count());
    }

    public function test_callbacks_are_refused_when_no_webhook_secret_is_configured(): void
    {
        $order = $this->order();

        config(['verified-crypto.webhook_secret' => null]);
        $this->app->forgetInstance(VerifiedCryptoSignature::class);

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ])->assertUnauthorized();
    }

    public function test_an_unsettled_status_is_acknowledged_without_marking_the_order_paid(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'pending',
            'tx_hash' => '0xabc123',
        ])->assertOk()->assertJson(['ok' => true, 'handled' => false]);

        $this->assertSame('awaiting-payment', $order->fresh()->status);
        $this->assertSame(0, Transaction::count());
    }

    public function test_the_settled_amount_is_recorded_rather_than_the_order_total(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'confirmed',
            'tx_hash' => '0xabc123',
            'amount' => '130.00',
            'value_forwarded_coin' => '124.80',
            'coin' => 'polygon_usdc',
        ])->assertOk();

        $transaction = Transaction::where('order_id', $order->id)->sole();

        $this->assertSame(13000, $transaction->amount->value);
        $this->assertSame(12550, $transaction->meta['order_total']);
        $this->assertSame('124.80', $transaction->meta['value_forwarded_coin']);
    }

    public function test_an_underpaid_callback_does_not_mark_the_order_paid(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'confirmed',
            'tx_hash' => '0xabc123',
            'amount' => '5.00',
        ])->assertStatus(422);

        $this->assertSame('awaiting-payment', $order->fresh()->status);
        $this->assertNull($order->fresh()->placed_at);
        $this->assertSame(0, Transaction::count());
    }

    public function test_rounding_slack_does_not_block_an_otherwise_exact_payment(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'confirmed',
            'tx_hash' => '0xabc123',
            'amount' => '125.49',
        ])->assertOk();

        $this->assertSame('payment-received', $order->fresh()->status);
    }

    public function test_the_documented_payment_confirmed_payload_settles_the_order(): void
    {
        $order = $this->order(['meta' => ['verified_crypto' => ['session_id' => 'sess_doc']]]);

        $this->sendCallback([
            'event' => 'payment.confirmed',
            'partner_id' => 'nanochecks',
            'session_id' => 'sess_doc',
            'order_id' => $order->reference,
            'status' => 'confirmed',
            'coin' => 'polygon_usdc',
            'amount' => '125.50',
            'currency' => 'USD',
            'address_in' => '0xPREPARED',
            'tx_hash' => '0xabc123',
            'txid_in' => '0xdef456',
            'txid_out' => '0xabc123',
            'value_forwarded_coin' => '123.00',
            'uuid' => 'cbdd11b2',
            'confirmed_at' => '2026-03-15T14:32:11Z',
        ])->assertOk()->assertJson(['handled' => true]);

        $this->assertSame('payment-received', $order->fresh()->status);
    }

    public function test_an_event_other_than_payment_confirmed_is_acknowledged_but_not_settled(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'event' => 'payment.pending',
            'order_id' => $order->reference,
            'status' => 'confirmed',
            'tx_hash' => '0xabc123',
        ])->assertOk()->assertJson(['handled' => false]);

        $this->assertSame('awaiting-payment', $order->fresh()->status);
        $this->assertSame(0, Transaction::count());
    }

    public function test_a_session_id_that_does_not_match_the_order_is_rejected(): void
    {
        $order = $this->order(['meta' => ['verified_crypto' => ['session_id' => 'sess_original']]]);

        $this->sendCallback([
            'event' => 'payment.confirmed',
            'session_id' => 'sess_someone_elses',
            'order_id' => $order->reference,
            'tx_hash' => '0xabc123',
        ])->assertStatus(422);

        $this->assertSame('awaiting-payment', $order->fresh()->status);
        $this->assertSame(0, Transaction::count());
    }

    /**
     * The payload VERIFIED's developer described for live partner-API
     * confirmations (2026-09-15): event, amount, coin, value_coin, tx_hash —
     * no status field, coin may be ETH on Ethereum.
     */
    public function test_the_live_payload_described_by_verified_settles_the_order(): void
    {
        $order = $this->order(['total' => 3500, 'meta' => ['verified_crypto' => ['session_id' => 'sess_0245abd0302b']]]);

        $this->sendCallback([
            'event' => 'payment.confirmed',
            'order_id' => $order->reference,
            'session_id' => 'sess_0245abd0302b',
            'amount' => '35.00',
            'coin' => 'eth',
            'value_coin' => '0.01231276',
            'tx_hash' => '0xb6aead75bbd682672f2eabc7867a5e376d43dcb941e90eb247754fc32d1c78ab',
        ])->assertOk()->assertJson(['ok' => true, 'handled' => true]);

        $order->refresh();
        $transaction = Transaction::where('order_id', $order->id)->sole();

        $this->assertSame('payment-received', $order->status);
        $this->assertSame(3500, $transaction->amount->value);
        $this->assertSame('eth', $transaction->card_type);
        $this->assertSame('0.01231276', $transaction->meta['value_coin']);
        $this->assertSame('Settled as 0.01231276 ETH on Ethereum.', $transaction->notes);
    }

    public function test_a_confirmation_without_order_id_is_matched_by_session_id(): void
    {
        $order = $this->order(['total' => 3500, 'meta' => ['verified_crypto' => ['session_id' => 'sess_only']]]);

        $this->sendCallback([
            'event' => 'payment.confirmed',
            'session_id' => 'sess_only',
            'amount' => '35.00',
            'coin' => 'eth',
            'value_coin' => '0.0123',
            'tx_hash' => '0xbysession',
        ])->assertOk()->assertJson(['handled' => true]);

        $this->assertSame('payment-received', $order->fresh()->status);
    }

    public function test_a_duplicate_of_the_live_payload_still_returns_200(): void
    {
        $order = $this->order(['total' => 3500]);
        $payload = ['event' => 'payment.confirmed', 'order_id' => $order->reference, 'amount' => '35.00', 'coin' => 'eth', 'value_coin' => '0.0123', 'tx_hash' => '0xdup'];

        $this->sendCallback($payload)->assertOk();
        $this->sendCallback($payload)->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(1, Transaction::where('order_id', $order->id)->count());
    }

    public function test_a_callback_for_an_unknown_order_is_a_404(): void
    {
        $this->sendCallback([
            'order_id' => 'NOPE-9999',
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ])->assertNotFound();
    }

    public function test_a_settlement_callback_without_a_tx_hash_is_not_authorized(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
        ])->assertStatus(422);

        $this->assertSame('awaiting-payment', $order->fresh()->status);
        $this->assertSame(0, Transaction::count());
    }

    public function test_the_endpoint_is_closed_when_the_integration_is_disabled(): void
    {
        config(['verified-crypto.enabled' => false]);

        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ])->assertNotFound();

        $this->assertSame(0, Transaction::count());
    }
}
