<?php

namespace Tests\Feature;

use App\Filament\Clusters\WebsiteLists\Pages\ContactFaqList;
use App\Models\WebsiteListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class AdminWebsiteListsTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsAdmin(): void
    {
        $this->actingAs(Staff::factory()->create(['admin' => true]), 'staff');
    }

    /**
     * Admin and storefront use different guards; after driving the admin,
     * browse the public site as a guest again.
     */
    private function browseStorefront(): static
    {
        $this->app['auth']->shouldUse('web');

        return $this;
    }

    public function test_each_list_has_its_own_admin_page(): void
    {
        $this->signInAsAdmin();

        $this->get('/lunar/website-lists/contact-faq')
            ->assertOk()
            ->assertSee('Frequently asked questions')
            ->assertSee('How fast do orders ship?');

        $this->get('/lunar/website-lists/about-commitments')
            ->assertOk()
            ->assertSee('Transparent Results');

        $this->get('/lunar/website-lists/global-trust')
            ->assertOk()
            ->assertSee('Trust bar promises');
    }

    public function test_saving_the_repeater_replaces_the_list_items_in_order(): void
    {
        $this->signInAsAdmin();

        Livewire::test(ContactFaqList::class)
            ->fillForm([
                'items' => [
                    ['heading' => 'Only question left?', 'body' => 'Only answer left.'],
                    ['heading' => 'A brand new question?', 'body' => 'A brand new answer.'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $items = WebsiteListItem::query()->where('list_key', 'contact.faq')->orderBy('sort_order')->get();

        $this->assertSame(['Only question left?', 'A brand new question?'], $items->pluck('heading')->all());

        $this->browseStorefront()->get('/contact')
            ->assertOk()
            ->assertSeeInOrder(['Only question left?', 'A brand new question?'])
            ->assertDontSee('How fast do orders ship?');
    }

    public function test_an_empty_list_saves_and_renders_nothing(): void
    {
        $this->signInAsAdmin();

        Livewire::test(ContactFaqList::class)
            ->fillForm(['items' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, WebsiteListItem::query()->where('list_key', 'contact.faq')->count());
        $this->browseStorefront()->get('/contact')->assertOk()->assertDontSee('How fast do orders ship?');
    }
}
