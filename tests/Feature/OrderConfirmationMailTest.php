<?php

namespace Tests\Feature;

use App\Listeners\SendOrderConfirmation;
use App\Mail\OrderConfirmation;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Lunar\Facades\CartSession;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Channel;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\OrderAddress;
use Lunar\Models\OrderLine;
use Lunar\Models\Price;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use RuntimeException;
use Tests\TestCase;

class OrderConfirmationMailTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);
        Channel::factory()->create(['default' => true]);
        TaxClass::factory()->create(['default' => true]);
        $this->country = Country::factory()->create(['name' => 'United States']);

        config([
            'verified-crypto.enabled' => true,
            'verified-crypto.webhook_secret' => self::SECRET,
        ]);
    }

    private function order(array $attributes = [], ?string $email = 'ada@example.com'): Order
    {
        $order = Order::factory()->create(array_merge([
            'status' => 'awaiting-payment',
            'placed_at' => null,
            'sub_total' => 12550,
            'shipping_total' => 900,
            'tax_total' => 0,
            'total' => 13450,
            'meta' => [],
        ], $attributes));

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'description' => 'BPC-157',
            'option' => '5mg',
            'quantity' => 2,
            'sub_total' => 12550,
        ]);

        OrderAddress::factory()->create([
            'order_id' => $order->id,
            'type' => 'shipping',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company_name' => null,
            'line_one' => '1 Research Way',
            'line_two' => null,
            'city' => 'Austin',
            'state' => 'TX',
            'postcode' => '73301',
            'country_id' => $this->country->id,
            'contact_email' => $email,
        ]);

        return $order->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendCallback(array $payload): TestResponse
    {
        $body = json_encode($payload);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, self::SECRET);

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

    public function test_a_settled_payment_callback_emails_the_customer_their_confirmation(): void
    {
        Mail::fake();
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ])->assertOk()->assertJson(['ok' => true, 'handled' => true]);

        Mail::assertSent(OrderConfirmation::class, function (OrderConfirmation $mail) use ($order) {
            return $mail->hasTo('ada@example.com') && $mail->order->is($order);
        });

        $this->assertNotEmpty($order->fresh()->meta['confirmation_emailed_at']);
    }

    public function test_a_repeated_callback_does_not_email_the_customer_twice(): void
    {
        Mail::fake();
        $order = $this->order();

        $payload = ['order_id' => $order->reference, 'status' => 'success', 'tx_hash' => '0xabc123'];

        $this->sendCallback($payload)->assertOk();
        $this->sendCallback($payload)->assertOk();

        Mail::assertSent(OrderConfirmation::class, 1);
    }

    public function test_the_confirmation_is_only_sent_once_per_order(): void
    {
        Mail::fake();
        $order = $this->order();

        $listener = app(SendOrderConfirmation::class);
        $listener->send($order);
        $listener->send($order->fresh());

        Mail::assertSent(OrderConfirmation::class, 1);
    }

    public function test_an_unsettled_callback_does_not_email_the_customer(): void
    {
        Mail::fake();
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'pending',
            'tx_hash' => '0xabc123',
        ])->assertOk()->assertJson(['handled' => false]);

        Mail::assertNothingSent();
    }

    public function test_an_order_without_an_email_address_is_skipped_without_failing(): void
    {
        Mail::fake();
        $order = $this->order(email: null);

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ])->assertOk()->assertJson(['ok' => true, 'handled' => true]);

        Mail::assertNothingSent();
        $this->assertNotNull($order->fresh()->placed_at);
    }

    public function test_a_mail_failure_does_not_fail_the_settlement_callback(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('mailgun is down'));
        $order = $this->order();

        $this->sendCallback([
            'order_id' => $order->reference,
            'status' => 'success',
            'tx_hash' => '0xabc123',
        ])->assertOk()->assertJson(['ok' => true, 'handled' => true]);

        $order->refresh();

        $this->assertNotNull($order->placed_at);
        $this->assertArrayNotHasKey('confirmation_emailed_at', (array) $order->meta);
    }

    public function test_the_confirmation_email_shows_the_order_details(): void
    {
        $order = $this->order(['placed_at' => now()]);

        $mail = new OrderConfirmation($order);
        $html = $mail->render();

        $this->assertStringContainsString("Order {$order->reference} confirmed", $mail->envelope()->subject);
        $this->assertStringContainsString('Order confirmed', $html);
        $this->assertStringContainsString('Thanks, Ada', $html);
        $this->assertStringContainsString($order->reference, $html);
        $this->assertStringContainsString('BPC-157 — 5mg', $html);
        $this->assertStringContainsString('$125.50', $html);
        $this->assertStringContainsString('$9', $html);
        $this->assertStringContainsString('$134.50', $html);
        $this->assertStringContainsString('1 Research Way', $html);
        $this->assertStringContainsString('Austin, TX 73301', $html);
        $this->assertStringContainsString('United States', $html);
        $this->assertStringContainsString(route('checkout.confirmation', $order->reference), $html);
        $this->assertStringContainsString('research use only', $html);
    }

    public function test_free_shipping_is_labelled_rather_than_shown_as_zero(): void
    {
        $order = $this->order(['shipping_total' => 0, 'total' => 12550]);

        $this->assertStringContainsString('Free', (new OrderConfirmation($order))->render());
    }

    public function test_an_unpaid_order_is_worded_as_received_rather_than_confirmed(): void
    {
        $order = $this->order();

        $mail = new OrderConfirmation($order);

        $this->assertStringContainsString("Order {$order->reference} received", $mail->envelope()->subject);
        $this->assertStringContainsString('Order received', $mail->render());
    }

    public function test_offline_checkout_emails_the_confirmation_immediately(): void
    {
        Mail::fake();
        config(['verified-crypto.enabled' => false]);

        $product = Product::factory()->create([
            'product_type_id' => ProductType::query()->firstOrCreate(['name' => 'Peptide'])->id,
            'status' => 'published',
            'attribute_data' => ['name' => new TranslatedText(collect(['en' => new Text('Test Peptide')]))],
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 100,
            'purchasable' => 'always',
        ]);

        Price::factory()->create([
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
            'currency_id' => Currency::query()->where('default', true)->sole()->id,
            'price' => 12550,
            'min_quantity' => 1,
        ]);

        CartSession::add($variant, 1);

        $this->withSession(['research_disclaimer_accepted' => true])
            ->post(route('checkout.store'), [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'email' => 'ada@example.com',
                'line_one' => '1 Research Way',
                'city' => 'Austin',
                'state' => 'TX',
                'postcode' => '73301',
                'country_id' => $this->country->id,
                'shipping_option' => 'STANDARD',
                'research_use_confirmed' => '1',
            ])
            ->assertRedirect();

        $order = Order::query()->sole();

        Mail::assertSent(OrderConfirmation::class, fn (OrderConfirmation $mail) => $mail->hasTo('ada@example.com') && $mail->order->is($order));
    }
}
