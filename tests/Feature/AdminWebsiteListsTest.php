<?php

namespace Tests\Feature;

use App\Models\WebsiteListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class AdminWebsiteListsTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsAdmin(): void
    {
        $staff = Staff::factory()->create(['admin' => true]);

        $this->actingAs($staff, 'staff');
    }

    public function test_the_website_lists_screen_loads_for_an_admin(): void
    {
        $this->signInAsAdmin();

        $this->get('/lunar/website-lists')
            ->assertOk()
            ->assertSee('Website Lists')
            ->assertSee('Transparent Results')
            ->assertSee('How fast do orders ship?');
    }

    public function test_the_add_item_and_edit_item_screens_load(): void
    {
        $this->signInAsAdmin();

        $this->get('/lunar/website-lists/create')->assertOk();

        $item = WebsiteListItem::query()->where('list_key', 'contact.faq')->firstOrFail();

        $this->get("/lunar/website-lists/{$item->id}/edit")
            ->assertOk()
            ->assertSee($item->heading);
    }
}
