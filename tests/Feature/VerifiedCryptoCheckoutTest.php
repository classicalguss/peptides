<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Lunar\Facades\CartSession;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Channel;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\Price;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use Tests\TestCase;

class VerifiedCryptoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);
        Channel::factory()->create(['default' => true]);
        TaxClass::factory()->create(['default' => true]);
        $this->country = Country::factory()->create();

        config([
            'verified-crypto.enabled' => true,
            'verified-crypto.wallet_address' => '0x6D9561903CCCA44f492b76F7a36656cE2b3f0D98',
            'verified-crypto.partner_id' => 'nanochecks',
            'verified-crypto.webhook_secret' => 'test-webhook-secret',
        ]);
    }

    private function cartWithOneItem(): void
    {
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
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(): array
    {
        return [
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
        ];
    }

    private function submitCheckout(): TestResponse
    {
        $this->cartWithOneItem();

        return $this->withSession(['research_disclaimer_accepted' => true])
            ->post(route('checkout.store'), $this->checkoutPayload());
    }

    public function test_checkout_creates_a_session_and_redirects_to_the_hosted_page(): void
    {
        Http::fake([
            '*/v1/partner-session' => Http::response([
                'ok' => true,
                'status' => 'success',
                'routing_mode' => 'auto',
                'session_id' => 'sess_abc',
                'checkout_url' => 'https://go.verifiedcryptocheckout.com/pay.php?token=opaque&x=1',
                'expires_at' => '2026-09-09T20:24:48.130Z',
            ]),
        ]);

        $this->submitCheckout()
            ->assertRedirect('https://go.verifiedcryptocheckout.com/pay.php?token=opaque&x=1');

        $order = Order::query()->sole();

        $this->assertNull($order->placed_at, 'The order must not be placed before settlement confirms.');
        $this->assertSame('sess_abc', $order->meta['verified_crypto']['session_id']);

        Http::assertSent(function ($request) use ($order) {
            $body = $request->data();

            return $request->url() === 'https://partnerapi.verifiedcryptocheckout.com/v1/partner-session'
                && $body['partner_id'] === 'nanochecks'
                && $body['address'] === '0x6D9561903CCCA44f492b76F7a36656cE2b3f0D98'
                && $body['amount'] === (float) $order->total->decimal
                && $body['currency'] === 'USD'
                && $body['email'] === 'ada@example.com'
                && $body['order_id'] === $order->reference
                && $body['callback'] === route('webhooks.verified-crypto')
                && $body['webhook_secret'] === 'test-webhook-secret';
        });
    }

    public function test_a_failed_session_returns_the_customer_to_checkout_without_placing_the_order(): void
    {
        Http::fake(['*/v1/partner-session' => Http::response(['ok' => false], 500)]);

        $this->submitCheckout()
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $this->assertNull(Order::query()->sole()->placed_at);
    }

    public function test_a_misconfigured_wallet_address_stops_the_payment_before_any_request_is_sent(): void
    {
        Http::fake();
        config(['verified-crypto.wallet_address' => 'not-an-address']);

        $this->submitCheckout()->assertSessionHasErrors('payment');

        Http::assertNothingSent();
    }

    public function test_an_order_below_the_minimum_is_rejected_before_any_session_is_created(): void
    {
        Http::fake();
        // The seeded cart totals $137.50; a $1,000 minimum puts it below the floor.
        config(['verified-crypto.minimum_order' => 100000]);

        $this->submitCheckout()
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $this->assertSame(0, Order::query()->count(), 'No order should be created below the minimum.');
        Http::assertNothingSent();
    }

    public function test_an_order_at_or_above_the_minimum_proceeds(): void
    {
        Http::fake([
            '*/v1/partner-session' => Http::response([
                'ok' => true, 'status' => 'success', 'session_id' => 'sess_min',
                'checkout_url' => 'https://go.verifiedcryptocheckout.com/pay.php?ok=1',
            ]),
        ]);
        config(['verified-crypto.minimum_order' => 3000]); // $30, below the $137.50 cart

        $this->submitCheckout()
            ->assertRedirect('https://go.verifiedcryptocheckout.com/pay.php?ok=1');

        $this->assertSame(1, Order::query()->count());
        Http::assertSentCount(1);
    }

    public function test_a_zero_minimum_disables_the_check(): void
    {
        Http::fake([
            '*/v1/partner-session' => Http::response([
                'ok' => true, 'status' => 'success', 'session_id' => 'sess_z',
                'checkout_url' => 'https://go.verifiedcryptocheckout.com/pay.php?ok=1',
            ]),
        ]);
        config(['verified-crypto.minimum_order' => 0]);

        $this->submitCheckout()->assertRedirect();

        $this->assertSame(1, Order::query()->count());
    }

    public function test_the_offline_flow_is_untouched_when_the_integration_is_disabled(): void
    {
        Http::fake();
        config(['verified-crypto.enabled' => false]);

        $this->submitCheckout()->assertRedirect(
            route('checkout.confirmation', Order::query()->sole()->reference)
        );

        Http::assertNothingSent();
        $this->assertNull(CartSession::current()?->id);
    }
}
