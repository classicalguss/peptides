<?php

namespace Tests\Feature;

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
use Lunar\Models\Price;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use Tests\TestCase;

class CheckoutShippingDestinationTest extends TestCase
{
    use RefreshDatabase;

    private Country $unitedStates;

    private Country $afghanistan;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);
        Channel::factory()->create(['default' => true]);
        TaxClass::factory()->create(['default' => true]);
        $this->afghanistan = Country::factory()->create(['name' => 'Afghanistan', 'iso2' => 'AF']);
        $this->unitedStates = Country::factory()->create(['name' => 'United States', 'iso2' => 'US']);

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
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submitCheckout(array $overrides = []): TestResponse
    {
        return $this->withSession(['research_disclaimer_accepted' => true])
            ->from(route('checkout'))
            ->post(route('checkout.store'), array_merge([
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'email' => 'ada@example.com',
                'line_one' => '1 Research Way',
                'city' => 'Austin',
                'state' => 'TX',
                'postcode' => '73301',
                'shipping_option' => 'STANDARD',
                'research_use_confirmed' => '1',
            ], $overrides));
    }

    public function test_an_order_ships_to_the_united_states_without_the_customer_choosing_a_country(): void
    {
        $this->submitCheckout()->assertSessionHasNoErrors();

        $address = Order::query()->sole()->shippingAddress;

        $this->assertSame($this->unitedStates->id, $address->country_id);
        $this->assertSame('TX', $address->state);
    }

    public function test_a_submitted_country_cannot_override_the_united_states(): void
    {
        $this->submitCheckout(['country_id' => $this->afghanistan->id])->assertSessionHasNoErrors();

        $this->assertSame($this->unitedStates->id, Order::query()->sole()->shippingAddress->country_id);
    }

    public function test_a_state_outside_the_united_states_is_rejected(): void
    {
        $this->submitCheckout(['state' => 'Amman'])
            ->assertRedirect(route('checkout'))
            ->assertSessionHasErrors(['state' => 'Please choose a US state. We only ship within the United States.']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_postcode_that_is_not_a_us_zip_is_rejected(): void
    {
        $this->submitCheckout(['postcode' => 'SW1A 1AA'])
            ->assertSessionHasErrors(['postcode' => 'Please enter a valid US ZIP code.']);

        $this->submitCheckout(['postcode' => '73301-1234'])->assertSessionHasNoErrors();
    }

    public function test_the_checkout_page_offers_us_states_and_no_country_choice(): void
    {
        $this->withSession(['research_disclaimer_accepted' => true])
            ->get(route('checkout'))
            ->assertOk()
            ->assertSee('<option value="TX"', false)
            ->assertSee('we ship within the US only')
            ->assertDontSee('name="country_id"', false)
            ->assertDontSee('Afghanistan');
    }
}
