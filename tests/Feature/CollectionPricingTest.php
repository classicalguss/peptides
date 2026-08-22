<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StackComponent;
use App\Models\StackTier;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Tests\TestCase;

class CollectionPricingTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        $this->currency = Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);
    }

    /**
     * @return array{0: Product, 1: ProductVariant}
     */
    private function product(string $name, string $typeName, int $priceCents): array
    {
        $created = Product::factory()->create([
            'product_type_id' => ProductType::query()->firstOrCreate(['name' => $typeName])->id,
            'status' => 'published',
            'attribute_data' => ['name' => new TranslatedText(collect(['en' => new Text($name)]))],
        ]);
        $variant = ProductVariant::factory()->create(['product_id' => $created->id]);
        Price::factory()->create(['priceable_type' => $variant->getMorphClass(), 'priceable_id' => $variant->id, 'currency_id' => $this->currency->id, 'price' => $priceCents, 'min_quantity' => 1]);

        return [Product::query()->with('variants.prices')->findOrFail($created->id), $variant];
    }

    public function test_a_tier_price_is_the_lunar_variant_price_and_follows_repricing(): void
    {
        [$stack, $variant] = $this->product('Test Collection', Product::TYPE_COLLECTION, 15000);
        $tier = StackTier::create(['product_id' => $stack->id, 'product_variant_id' => $variant->id, 'code' => 'HP', 'label' => 'Core', 'supply_days' => 40, 'position' => 1]);

        $this->assertSame(15000, $tier->fresh()->priceValue());

        // Reprice in the standard Lunar place; the storefront must follow.
        $variant->prices()->first()->update(['price' => 16000]);

        $this->assertSame(16000, $tier->fresh()->priceValue());
    }

    public function test_savings_are_derived_from_component_prices_not_stored(): void
    {
        [$stack, $variant] = $this->product('Test Collection', Product::TYPE_COLLECTION, 15000);
        [$a] = $this->product('Compound A', Product::TYPE_COMPOUND, 8000);
        [$b] = $this->product('Compound B', Product::TYPE_COMPOUND, 12000);
        $tier = StackTier::create(['product_id' => $stack->id, 'product_variant_id' => $variant->id, 'code' => 'HP', 'label' => 'Core', 'supply_days' => 40, 'position' => 1]);
        StackComponent::create(['stack_product_id' => $stack->id, 'component_product_id' => $a->id, 'base_quantity' => 1, 'position' => 1]);
        StackComponent::create(['stack_product_id' => $stack->id, 'component_product_id' => $b->id, 'base_quantity' => 1, 'position' => 2]);

        $tiers = StackTier::where('product_id', $stack->id)->with('variant.prices')->get();
        $components = StackComponent::where('stack_product_id', $stack->id)->get();
        $componentProducts = Catalog::componentProducts($components);

        $retail = Catalog::retailValues($tiers, $components, $componentProducts);
        $this->assertSame(['HP' => 20000], $retail);                     // 8000 + 12000 bought separately
        $this->assertSame(['HP' => 25.0], Catalog::savings($tiers, $retail)); // 15000 vs 20000
        $this->assertSame(25.0, Catalog::saveUpTo($stack));
    }
}
