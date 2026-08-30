<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StackComponent;
use App\Models\StackTier;
use App\Models\StackTierQuantity;
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
        $componentA = StackComponent::create(['stack_product_id' => $stack->id, 'component_product_id' => $a->id, 'position' => 1]);
        $componentB = StackComponent::create(['stack_product_id' => $stack->id, 'component_product_id' => $b->id, 'position' => 2]);
        StackTierQuantity::create(['stack_tier_id' => $tier->id, 'stack_component_id' => $componentA->id, 'quantity' => 1]);
        StackTierQuantity::create(['stack_tier_id' => $tier->id, 'stack_component_id' => $componentB->id, 'quantity' => 1]);

        $tiers = StackTier::where('product_id', $stack->id)->with('variant.prices')->get();
        $components = StackComponent::where('stack_product_id', $stack->id)->with('tierQuantities')->get();
        $componentProducts = Catalog::componentProducts($components);

        $retail = Catalog::retailValues($tiers, $components, $componentProducts);
        $this->assertSame(['HP' => 20000], $retail);                     // 8000 + 12000 bought separately
        $this->assertSame(['HP' => 25.0], Catalog::savings($tiers, $retail)); // 15000 vs 20000
        $this->assertSame(25.0, Catalog::saveUpTo($stack));
    }

    public function test_each_tier_has_its_own_vial_counts_and_zero_means_not_included(): void
    {
        [$stack, $variantHp] = $this->product('Test Collection', Product::TYPE_COLLECTION, 15000);
        [$a] = $this->product('Compound A', Product::TYPE_COMPOUND, 8000);
        [$b] = $this->product('Compound B', Product::TYPE_COMPOUND, 1000);
        $variantZ = ProductVariant::factory()->create(['product_id' => $stack->id]);
        Price::factory()->create(['priceable_type' => $variantZ->getMorphClass(), 'priceable_id' => $variantZ->id, 'currency_id' => $this->currency->id, 'price' => 30000, 'min_quantity' => 1]);

        $tierHp = StackTier::create(['product_id' => $stack->id, 'product_variant_id' => $variantHp->id, 'code' => 'HP', 'label' => 'Core', 'supply_days' => 40, 'position' => 1]);
        $tierZ = StackTier::create(['product_id' => $stack->id, 'product_variant_id' => $variantZ->id, 'code' => 'Z', 'label' => 'Plus', 'supply_days' => 80, 'position' => 2]);
        $componentA = StackComponent::create(['stack_product_id' => $stack->id, 'component_product_id' => $a->id, 'position' => 1]);
        $componentB = StackComponent::create(['stack_product_id' => $stack->id, 'component_product_id' => $b->id, 'position' => 2]);

        // Z is not a uniform multiple of HP: 3× compound A but only 2× compound B.
        StackTierQuantity::create(['stack_tier_id' => $tierHp->id, 'stack_component_id' => $componentA->id, 'quantity' => 1]);
        StackTierQuantity::create(['stack_tier_id' => $tierHp->id, 'stack_component_id' => $componentB->id, 'quantity' => 5]);
        StackTierQuantity::create(['stack_tier_id' => $tierZ->id, 'stack_component_id' => $componentA->id, 'quantity' => 3]);
        StackTierQuantity::create(['stack_tier_id' => $tierZ->id, 'stack_component_id' => $componentB->id, 'quantity' => 10]);

        $tiers = StackTier::where('product_id', $stack->id)->with('variant.prices')->get();
        $components = StackComponent::where('stack_product_id', $stack->id)->with('tierQuantities')->get();

        $this->assertSame(1, $componentA->fresh()->quantityForTier($tierHp));
        $this->assertSame(3, $componentA->fresh()->quantityForTier($tierZ));

        $retail = Catalog::retailValues($tiers, $components, Catalog::componentProducts($components));
        $this->assertSame(['HP' => 13000, 'Z' => 34000], $retail); // 8000×1 + 1000×5, 8000×3 + 1000×10

        // A compound with no quantity row for a tier counts as not included.
        StackTierQuantity::where('stack_tier_id', $tierZ->id)->where('stack_component_id', $componentB->id)->delete();
        $components = StackComponent::where('stack_product_id', $stack->id)->with('tierQuantities')->get();

        $this->assertSame(0, $componentB->fresh()->quantityForTier($tierZ));
        $this->assertSame(['HP' => 13000, 'Z' => 24000], Catalog::retailValues($tiers, $components, Catalog::componentProducts($components)));
    }
}
