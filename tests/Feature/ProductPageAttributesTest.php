<?php

namespace Tests\Feature;

use App\FieldTypes\Textarea;
use App\FieldTypes\TextList;
use App\Models\Product;
use App\Support\WebsitePageAttributes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\FieldTypes\Dropdown;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\Language;
use Lunar\Models\ProductType;
use Tests\TestCase;

class ProductPageAttributesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
    }

    public function test_page_text_is_read_from_the_website_page_attributes(): void
    {
        $created = Product::factory()->create([
            'product_type_id' => ProductType::query()->firstOrCreate(['name' => Product::TYPE_COMPOUND])->id,
            'attribute_data' => [
                'name' => new TranslatedText(collect(['en' => new Text('BPC-157 20mg')])),
                'subtitle' => new Text('Short description'),
                'overview' => new Textarea("Line one.\nLine two."),
                'highlights' => new TextList(['First', ' ', 'Second']),
                'accent' => new Dropdown('lime'),
                'display_order' => new Number(3),
            ],
        ]);

        $product = Product::query()->findOrFail($created->id);

        $this->assertSame('Short description', $product->subtitle);
        $this->assertSame("Line one.\nLine two.", $product->overview);
        $this->assertSame(['First', 'Second'], $product->highlights);
        $this->assertNull($product->research_info);
        $this->assertSame([], $product->pillars);
        $this->assertSame('lime', $product->accent);
        $this->assertSame(config('theme.accents.lime.hex'), $product->accentHex());
        $this->assertSame(3, $product->displayOrder());
    }

    public function test_products_without_page_attributes_fall_back_to_the_gold_accent(): void
    {
        $created = Product::factory()->create([
            'product_type_id' => ProductType::query()->firstOrCreate(['name' => Product::TYPE_COMPOUND])->id,
            'attribute_data' => ['name' => new TranslatedText(collect(['en' => new Text('Bare')]))],
        ]);

        $product = Product::query()->findOrFail($created->id);

        $this->assertSame('gold', $product->accent);
        $this->assertSame(0, $product->displayOrder());
        $this->assertStringContainsString(config('theme.brand.gold'), $product->accentStyle());
    }

    public function test_the_website_page_attributes_exist_and_are_mapped_per_product_type(): void
    {
        $compound = ProductType::query()->firstOrCreate(['name' => Product::TYPE_COMPOUND]);
        $collection = ProductType::query()->firstOrCreate(['name' => Product::TYPE_COLLECTION]);

        WebsitePageAttributes::ensure();
        WebsitePageAttributes::ensure(); // idempotent

        $this->assertSame(
            count(WebsitePageAttributes::definitions()),
            Attribute::query()->whereIn('handle', array_keys(WebsitePageAttributes::definitions()))->count()
        );

        $compoundHandles = $compound->mappedAttributes()->pluck('handle');
        $collectionHandles = $collection->mappedAttributes()->pluck('handle');

        $this->assertTrue($compoundHandles->contains('overview'));
        $this->assertFalse($collectionHandles->contains('overview'));
        $this->assertTrue($collectionHandles->contains('pillars'));
        $this->assertFalse($compoundHandles->contains('pillars'));
        $this->assertTrue($compoundHandles->contains('summary'));
        $this->assertTrue($collectionHandles->contains('summary'));
    }
}
