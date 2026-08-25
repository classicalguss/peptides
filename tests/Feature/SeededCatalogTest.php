<?php

namespace Tests\Feature;

use App\Models\CoaReport;
use App\Models\Policy;
use App\Models\Product;
use App\Models\WebsiteListItem;
use App\Models\WebsiteText;
use App\Support\Catalog;
use App\Support\WebsiteContent;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;
use Lunar\Models\TaxClass;
use Tests\TestCase;

/**
 * A fresh database built from the migrations, the content sync and the
 * catalog seeder must produce a working store.
 */
class SeededCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::factory()->create(['code' => 'en', 'default' => true]);
        Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);
        Channel::factory()->create(['default' => true]);
        CustomerGroup::factory()->create(['default' => true]);
        TaxClass::factory()->create(['default' => true]);

        $this->seed(CatalogSeeder::class);
    }

    public function test_the_seeder_builds_the_live_catalog(): void
    {
        $this->assertCount(10, Catalog::compounds());
        $this->assertCount(6, Catalog::stacks());
        $this->assertCount(10, CoaReport::all());

        $collection = Catalog::findBySlug('healing-stack');

        $this->assertInstanceOf(Product::class, $collection);
        $this->assertTrue($collection->isStack());
        $this->assertCount(3, $collection->tiers);
        $this->assertCount(3, $collection->components);
        $this->assertGreaterThan(0, Catalog::saveUpTo($collection));
        $this->assertTrue(Catalog::findBySlug('bac-water-10ml')?->isSupply());
    }

    public function test_every_storefront_page_renders_from_seeded_data(): void
    {
        WebsiteContent::sync();

        foreach (['/', '/shop', '/stacks', '/peptides/bpc-157-20mg', '/stacks/healing-stack', '/lab-reports', '/policies/privacy-policy'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/peptides/bpc-157-20mg')
            ->assertSee(Catalog::findBySlug('bpc-157-20mg')->subtitle)
            ->assertSee('Storage');
    }

    public function test_content_sync_registers_texts_lists_and_policies_without_overwriting_edits(): void
    {
        WebsiteContent::sync();

        $this->assertSame(count(config('website-text')), WebsiteText::count());
        $this->assertSame(count(config('website-lists')), WebsiteListItem::distinct()->count('list_key'));
        $this->assertCount(5, Policy::all());

        WebsiteText::where('key', 'gate.heading')->update(['value' => 'Edited by the client']);
        WebsiteContent::sync();

        $this->assertSame('Edited by the client', WebsiteText::where('key', 'gate.heading')->value('value'));
    }
}
