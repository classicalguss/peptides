<?php

namespace Tests\Feature;

use App\Models\WebsiteText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_registered_text_item_is_available_to_the_admin(): void
    {
        $this->assertSame(count(config('website-text')), WebsiteText::query()->count());
    }

    public function test_a_key_missing_from_the_database_falls_back_to_its_configured_default(): void
    {
        $key = 'collection_product.lab_title';
        $default = config('website-text')[$key]['default'];

        WebsiteText::query()->where('key', $key)->delete();
        cache()->forget('website-text.values');

        $this->assertSame($default, site_text($key));
    }

    public function test_an_admin_text_change_is_shown_on_the_storefront_and_escaped(): void
    {
        WebsiteText::query()
            ->where('key', 'home.hero_title_line_1')
            ->firstOrFail()
            ->update(['value' => 'Compliance-safe <script>alert("no")</script> title']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Compliance-safe')
            ->assertSee('&lt;script&gt;alert(&quot;no&quot;)&lt;/script&gt;', escape: false)
            ->assertDontSee('<script>alert("no")</script>', escape: false);
    }

    public function test_content_can_be_found_by_page_section_label_or_current_wording(): void
    {
        $term = 'eyebrow';

        $matches = WebsiteText::query()
            ->where(function ($query) use ($term) {
                $query->where('page', 'like', "%{$term}%")
                    ->orWhere('section', 'like', "%{$term}%")
                    ->orWhere('label', 'like', "%{$term}%")
                    ->orWhere('location_hint', 'like', "%{$term}%")
                    ->orWhere('value', 'like', "%{$term}%");
            })
            ->get();

        $this->assertNotEmpty($matches);
        $this->assertTrue($matches->contains('key', 'collections.hero_eyebrow'));
    }

    public function test_styled_editable_headings_still_escape_admin_input(): void
    {
        WebsiteText::query()
            ->where('key', 'collections.hero_title')
            ->firstOrFail()
            ->update(['value' => 'Research <script>alert("no")</script> Collections']);

        $this->get('/stacks')
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;no&quot;)&lt;/script&gt;', escape: false)
            ->assertDontSee('<script>alert("no")</script>', escape: false);
    }
}
