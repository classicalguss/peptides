<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;
use Lunar\Models\ProductType;
use Lunar\Models\Url;
use Tests\TestCase;

class StorefrontPageLookupTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $urlSlug, string $typeName = Product::TYPE_COMPOUND): Product
    {
        $language = Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);

        $created = Product::factory()->create([
            'product_type_id' => ProductType::query()->firstOrCreate(['name' => $typeName])->id,
            'status' => 'published',
            'attribute_data' => ['name' => new TranslatedText(collect(['en' => new Text('Test Compound')]))],
        ]);

        // Mirror an admin editing the URL in Lunar.
        $created->urls()->delete();
        Url::create(['element_type' => $created->getMorphClass(), 'element_id' => $created->id, 'language_id' => $language->id, 'slug' => $urlSlug, 'default' => true]);

        return Product::query()->findOrFail($created->id);
    }

    public function test_a_page_resolves_by_its_admin_editable_url_slug(): void
    {
        $product = $this->makeProduct('renamed-in-admin');

        $this->assertTrue(Catalog::findBySlug('renamed-in-admin')?->is($product));
    }

    public function test_lookups_return_the_app_product_model(): void
    {
        $this->makeProduct('some-compound');

        $this->assertInstanceOf(Product::class, Catalog::findBySlug('some-compound'));
    }

    public function test_unknown_slugs_resolve_to_nothing(): void
    {
        $this->makeProduct('renamed-in-admin');

        $this->assertNull(Catalog::findBySlug('does-not-exist'));
    }

    public function test_the_product_type_decides_which_page_a_product_gets(): void
    {
        $compound = $this->makeProduct('a-compound');
        $collection = $this->makeProduct('a-collection', Product::TYPE_COLLECTION);

        $this->assertFalse($compound->isStack());
        $this->assertTrue($collection->isStack());
        $this->assertSame(route('compound', 'a-compound'), $compound->storefrontUrl());
        $this->assertSame(route('stack', 'a-collection'), $collection->storefrontUrl());
    }
}
