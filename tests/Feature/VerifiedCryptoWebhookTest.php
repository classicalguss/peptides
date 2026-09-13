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
