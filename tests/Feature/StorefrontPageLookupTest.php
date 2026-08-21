<?php

namespace Tests\Feature;

use App\Models\ProductProfile;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\Url;
use Tests\TestCase;

class StorefrontPageLookupTest extends TestCase
{
    use RefreshDatabase;

    private function makeProfile(string $handle, string $urlSlug): ProductProfile
    {
        $language = Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);

        $product = Product::factory()->create([
            'product_type_id' => ProductType::factory()->create(['name' => 'Research Compound'])->id,
            'attribute_data' => ['name' => new TranslatedText(collect(['en' => new Text('Test Compound')]))],
        ]);

        // Mirror an admin editing the URL in Lunar: the slug no longer matches the handle.
        $product->urls()->delete();
        Url::create(['element_type' => $product->getMorphClass(), 'element_id' => $product->id, 'language_id' => $language->id, 'slug' => $urlSlug, 'default' => true]);

        return ProductProfile::create(['product_id' => $product->id, 'handle' => $handle, 'kind' => 'compound']);
    }

    public function test_a_page_resolves_by_its_admin_editable_url_slug(): void
    {
        $profile = $this->makeProfile('old-handle', 'renamed-in-admin');

        $this->assertTrue(Catalog::findByHandle('renamed-in-admin')?->is($profile));
    }

    public function test_the_old_handle_still_resolves_as_a_fallback(): void
    {
        $profile = $this->makeProfile('old-handle', 'renamed-in-admin');

        $this->assertTrue(Catalog::findByHandle('old-handle')?->is($profile));
    }

    public function test_unknown_slugs_resolve_to_nothing(): void
    {
        $this->makeProfile('old-handle', 'renamed-in-admin');

        $this->assertNull(Catalog::findByHandle('does-not-exist'));
    }
}
