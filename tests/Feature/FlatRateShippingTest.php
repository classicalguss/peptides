<?php

namespace Tests\Feature;

use App\Shipping\FlatRateShipping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Facades\CartSession;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use Tests\TestCase;

class FlatRateShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);
        Channel::factory()->create(['default' => true]);
        TaxClass::factory()->create(['default' => true]);
    }

    private function cartWithSubtotal(int $cents): void
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => Product::factory()->create(['status' => 'published'])->id,
            'stock' => 100,
            'purchasable' => 'always',
        ]);

        Price::factory()->create([
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
            'currency_id' => Currency::query()->where('default', true)->sole()->id,
            'price' => $cents,
            'min_quantity' => 1,
        ]);

        CartSession::add($variant, 1);
        CartSession::current()->calculate();
    }

    private function standardRate(): int
    {
        return ShippingManifest::getOption(CartSession::current(), 'STANDARD')->price->value;
    }

    public function test_standard_shipping_is_charged_below_the_free_threshold(): void
    {
        $this->cartWithSubtotal(5000);

        $this->assertSame(1200, $this->standardRate());
    }

    public function test_standard_shipping_is_free_at_or_above_the_threshold(): void
    {
        $this->cartWithSubtotal(20000);

        $this->assertSame(0, $this->standardRate());
    }

    public function test_a_zero_threshold_makes_every_order_ship_free(): void
    {
        config(['shipping.free_threshold' => 0]);

        $this->cartWithSubtotal(200);

        $this->assertSame(0, $this->standardRate());
        $this->assertSame(0, FlatRateShipping::freeShippingThreshold());
    }

    public function test_rates_can_be_overridden_without_a_deploy(): void
    {
        config(['shipping.standard_rate' => 999, 'shipping.express_rate' => 1999]);

        $this->cartWithSubtotal(5000);

        $this->assertSame(999, $this->standardRate());
        $this->assertSame(1999, ShippingManifest::getOption(CartSession::current(), 'EXPRESS')->price->value);
    }
}
