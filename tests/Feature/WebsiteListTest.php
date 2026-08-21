<?php

namespace Tests\Feature;

use App\Models\WebsiteListItem;
use App\Models\WebsiteText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteListTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_configured_list_is_seeded_with_its_default_items(): void
    {
        foreach (config('website-lists') as $key => $definition) {
            $this->assertSame(
                count($definition['defaults']),
                WebsiteListItem::query()->where('list_key', $key)->count(),
                "List {$key} was not seeded with its defaults.",
            );
        }
    }

    public function test_list_items_render_in_admin_order_and_can_be_added(): void
    {
        WebsiteListItem::query()->where('list_key', 'contact.faq')->delete();

        WebsiteListItem::create(['list_key' => 'contact.faq', 'sort_order' => 2, 'heading' => 'Second question?', 'body' => 'Second answer.']);
        WebsiteListItem::create(['list_key' => 'contact.faq', 'sort_order' => 1, 'heading' => 'First question?', 'body' => 'First answer.']);
        WebsiteListItem::create(['list_key' => 'contact.faq', 'sort_order' => 3, 'heading' => 'Brand new question?', 'body' => 'Brand new answer.']);

        $response = $this->get('/contact')->assertOk();

        $response->assertSeeInOrder(['First question?', 'Second question?', 'Brand new question?']);
        $response->assertSee('Brand new answer.');
    }

    public function test_removing_an_item_removes_it_from_the_page(): void
    {
        $item = WebsiteListItem::query()->where('list_key', 'contact.write_tips')->orderBy('sort_order')->firstOrFail();
        $text = $item->body;

        $this->get('/contact')->assertSee($text);

        $item->delete();

        $this->get('/contact')->assertDontSee($text);
        $this->assertSame(2, WebsiteListItem::query()->where('list_key', 'contact.write_tips')->count());
    }

    public function test_the_legacy_numbered_text_keys_no_longer_exist(): void
    {
        $legacy = [];

        foreach (config('website-lists') as $definition) {
            foreach ($definition['legacy_keys'] as $pattern) {
                foreach (range(1, 10) as $i) {
                    $legacy[] = str_replace('{i}', (string) $i, $pattern);
                }
            }
        }

        $this->assertEmpty(
            array_intersect($legacy, array_keys(config('website-text'))),
            'Repeating content must live in website-lists, not numbered website-text keys.',
        );
        $this->assertSame(0, WebsiteText::query()->whereIn('key', $legacy)->count());
    }
}
