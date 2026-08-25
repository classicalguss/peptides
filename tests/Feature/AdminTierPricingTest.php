<?php

namespace Tests\Feature;

use App\Filament\Support\Pages\ManageProductPricing;
use App\Filament\Support\Pages\ManageVariantPricing;
use App\Filament\Support\RelationManagers\PriceRelationManager;
use App\Models\Product;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductResource\RelationManagers\CustomerGroupPricingRelationManager;
use Lunar\Admin\Filament\Resources\ProductVariantResource;
use Lunar\Admin\Models\Staff;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Tests\TestCase;

class AdminTierPricingTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        $this->currency = Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);

        $product = Product::factory()->create([
            'product_type_id' => ProductType::query()->firstOrCreate(['name' => 'Research Compound'])->id,
            'status' => 'published',
            'attribute_data' => ['name' => new TranslatedText(collect(['en' => new Text('BPC-157 20mg')]))],
        ]);
        $this->variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        Price::factory()->create([
            'priceable_type' => $this->variant->getMorphClass(),
            'priceable_id' => $this->variant->id,
            'currency_id' => $this->currency->id,
            'price' => 8999,
            'min_quantity' => 1,
        ]);

        $this->actingAs(Staff::factory()->create(['admin' => true]), 'staff');
    }

    private function tierPricing(): Testable
    {
        return Livewire::test(PriceRelationManager::class, [
            'ownerRecord' => $this->variant,
            'pageClass' => ManageProductPricing::class,
        ]);
    }

    public function test_a_quantity_break_price_is_stored_at_the_exact_cents_entered(): void
    {
        $this->tierPricing()->callTableAction(CreateAction::class, data: [
            'currency_id' => $this->currency->id,
            'min_quantity' => 2,
            'price' => '79.99',
            'compare_price' => '89.99',
        ])->assertHasNoTableActionErrors();

        $tier = $this->variant->prices()->where('min_quantity', 2)->sole();

        $this->assertSame(7999, $tier->price->value);
        $this->assertSame(8999, $tier->compare_price->value);
    }

    public function test_editing_a_quantity_break_keeps_the_exact_cents_entered(): void
    {
        $tier = Price::factory()->create([
            'priceable_type' => $this->variant->getMorphClass(),
            'priceable_id' => $this->variant->id,
            'currency_id' => $this->currency->id,
            'price' => 7500,
            'compare_price' => 8999,
            'min_quantity' => 3,
        ]);

        $this->tierPricing()->callTableAction(EditAction::class, $tier, data: [
            'currency_id' => $this->currency->id,
            'min_quantity' => 3,
            'price' => '69.99',
            'compare_price' => '89.99',
        ])->assertHasNoTableActionErrors();

        $tier->refresh();

        $this->assertSame(6999, $tier->price->value);
        $this->assertSame(8999, $tier->compare_price->value);
    }

    public function test_the_pricing_screens_still_load(): void
    {
        $this->get('/lunar/products/'.$this->variant->product_id.'/pricing')->assertOk();
        $this->get('/lunar/product-variants/'.$this->variant->id.'/pricing')->assertOk();

        $this->assertSame(ManageProductPricing::class, ProductResource::getPages()['pricing']->getPage());
        $this->assertSame(ManageVariantPricing::class, ProductVariantResource::getPages()['pricing']->getPage());
        $this->assertContains(PriceRelationManager::class, (new ManageVariantPricing)->getRelationManagers());
    }

    public function test_a_customer_group_price_is_stored_at_the_exact_cents_entered(): void
    {
        Livewire::test(CustomerGroupPricingRelationManager::class, [
            'ownerRecord' => $this->variant,
            'pageClass' => ManageProductPricing::class,
        ])->callTableAction(CreateAction::class, data: [
            'currency_id' => $this->currency->id,
            'customer_group_id' => CustomerGroup::factory()->create()->id,
            'price' => '19.99',
            'compare_price' => '29.99',
        ])->assertHasNoTableActionErrors();

        $price = $this->variant->prices()->whereNotNull('customer_group_id')->sole();

        $this->assertSame(1999, $price->price->value);
        $this->assertSame(2999, $price->compare_price->value);
        $this->assertSame(1, $price->min_quantity);
    }
}
