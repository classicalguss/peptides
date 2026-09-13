<?php

namespace Tests\Feature;

use App\Payments\VerifiedCryptoSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\Transaction;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * VERIFIED do not publish the string they sign, so every plausible
     * construction is accepted. Each must work end to end.
     */
    #[DataProvider('signatureFormats')]
    public function test_it_accepts_any_documented_plausible_signature_format(string $format, bool $base64): void
    {
        $order = $this->order();

        $body = json_encode(['order_id' => $order->reference, 'status' => 'confirmed', 'tx_hash' => '0xfmt']);
        $ts = (string) now()->timestamp;

        $payload = match ($format) {
            'timestamp.body' => $ts.'.'.$body,
            'body' => $body,
            'timestamp+body' => $ts.$body,
            'body+timestamp' => $body.$ts,
        };

        $signature = $base64
            ? base64_encode(hash_hmac('sha256', $payload, self::SECRET, true))
            : hash_hmac('sha256', $payload, self::SECRET);

        $this->call('POST', route('webhooks.verified-crypto'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_VCC_TIMESTAMP' => $ts,
            'HTTP_X_VCC_SIGNATURE' => $signature,
        ], $body)->assertOk();

        $this->assertSame('payment-received', $order->fresh()->status);
    }

    public static function signatureFormats(): array
    {
        return [
            'stripe style, hex' => ['timestamp.body', false],
            'body only, hex' => ['body', false],
            'concatenated, hex' => ['timestamp+body', false],
            'body then timestamp, hex' => ['body+timestamp', false],
            'stripe style, base64' => ['timestamp.body', true],
            'body only, base64' => ['body', true],
        ];
    }

    public function test_a_callback_with_a_bad_signature_is_rejected_and_the_order_is_untouched(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ], secret: 'wrong-secret')->assertForbidden();

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
        ])->assertForbidden();

        $this->assertSame(0, Transaction::count());
    }

    public function test_a_stale_timestamp_is_rejected_as_a_replay(): void
    {
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ], timestamp: (string) now()->subHour()->timestamp)->assertForbidden();

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
        ])->assertForbidden();
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
