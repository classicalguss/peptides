<?php

namespace Tests\Feature;

use App\Models\Policy;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;
use Lunar\Models\ProductType;
use Lunar\Models\Url;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://powereduppeptides.com']);
    }

    private function makeProduct(string $slug, string $typeName = Product::TYPE_COMPOUND, string $status = 'published'): Product
    {
        $language = Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);

        $product = Product::factory()->create([
            'product_type_id' => ProductType::query()->firstOrCreate(['name' => $typeName])->id,
            'status' => $status,
            'attribute_data' => ['name' => new TranslatedText(collect(['en' => new Text('Test Product')]))],
        ]);

        $product->urls()->delete();
        Url::create(['element_type' => $product->getMorphClass(), 'element_id' => $product->id, 'language_id' => $language->id, 'slug' => $slug, 'default' => true]);

        return $product;
    }

    /**
     * @return array<int, string>
     */
    private function sitemapLocations(): array
    {
        $response = $this->get('/sitemap.xml')->assertOk();

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'The sitemap is not well-formed XML.');

        $locations = [];

        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        return $locations;
    }

    public function test_the_sitemap_is_served_as_xml(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', escape: false);
    }

    public function test_it_lists_the_main_pages_and_every_policy(): void
    {
        $locations = $this->sitemapLocations();

        foreach (['/', '/shop', '/stacks', '/lab-reports', '/about', '/contact'] as $path) {
            $this->assertContains('https://powereduppeptides.com'.$path, $locations);
        }

        foreach (Policy::pluck('slug') as $slug) {
            $this->assertContains("https://powereduppeptides.com/policies/{$slug}", $locations);
        }
    }

    public function test_it_lists_published_compounds_and_collections_with_lastmod(): void
    {
        $this->makeProduct('bpc-157');
        $this->makeProduct('healing-stack', Product::TYPE_COLLECTION);

        $locations = $this->sitemapLocations();

        $this->assertContains('https://powereduppeptides.com/peptides/bpc-157', $locations);
        $this->assertContains('https://powereduppeptides.com/stacks/healing-stack', $locations);
        $this->get('/sitemap.xml')->assertSee('<lastmod>', escape: false);
    }

    public function test_it_follows_catalog_changes_without_regeneration(): void
    {
        $product = $this->makeProduct('retired-compound');
        $this->makeProduct('draft-compound', status: 'draft');

        $this->assertContains('https://powereduppeptides.com/peptides/retired-compound', $this->sitemapLocations());
        $this->assertNotContains('https://powereduppeptides.com/peptides/draft-compound', $this->sitemapLocations());

        $product->delete();

        $this->assertNotContains('https://powereduppeptides.com/peptides/retired-compound', $this->sitemapLocations());
    }

    public function test_it_excludes_private_pages(): void
    {
        $locations = implode("\n", $this->sitemapLocations());

        foreach (['/cart', '/checkout', '/account', '/login', '/register'] as $path) {
            $this->assertStringNotContainsString($path, $locations);
        }
    }

    public function test_urls_use_the_app_url_host_even_when_requested_on_another_host(): void
    {
        $this->makeProduct('bpc-157');

        $this->get('https://www.powereduppeptides.com/sitemap.xml')
            ->assertOk()
            ->assertSee('https://powereduppeptides.com/peptides/bpc-157', escape: false)
            ->assertDontSee('www.powereduppeptides.com', escape: false);
    }

    public function test_public_pages_carry_a_query_free_canonical_on_the_app_url_host(): void
    {
        $this->get('https://www.powereduppeptides.com/shop?category=healing&sort=name')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://powereduppeptides.com/shop">', escape: false)
            ->assertDontSee('name="robots"', escape: false);

        $this->get('/')
            ->assertSee('<link rel="canonical" href="https://powereduppeptides.com/">', escape: false);
    }

    public function test_cart_and_auth_pages_are_noindex_without_a_canonical(): void
    {
        foreach (['/cart', '/login', '/register'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex">', escape: false)
                ->assertDontSee('rel="canonical"', escape: false);
        }
    }

    public function test_robots_txt_points_at_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://powereduppeptides.com/sitemap.xml', $robots);
        $this->assertMatchesRegularExpression('/^Disallow:\s*$/m', $robots);
    }
}
