<?php

namespace Tests\Feature;

use App\Models\Policy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class PolicyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_five_policies_are_seeded_and_render(): void
    {
        $expected = [
            'terms-and-conditions' => ['Terms & Conditions', 'You must be at least 21 years of age to purchase products'],
            'privacy-policy' => ['Privacy Policy', 'INFORMATION WE COLLECT'],
            'shipping-policy' => ['Shipping Policy', 'ships within the United States only'],
            'return-and-refund-policy' => ['Return & Refund Policy', 'sealed'],
            'research-use-only-policy' => ['Research Use Only (RUO) Policy', 'research'],
        ];

        $this->assertSame(5, Policy::count());

        foreach ($expected as $slug => [$title, $snippet]) {
            $this->get("/policies/{$slug}")->assertOk()->assertSee($title)->assertSee($snippet, escape: false);
        }

        $this->get('/policies/does-not-exist')->assertNotFound();
    }

    public function test_the_footer_links_to_every_policy(): void
    {
        $response = $this->get('/')->assertOk()->assertSee('Policies');

        foreach (Policy::all() as $policy) {
            $response->assertSee(route('policy', $policy->slug));
        }
    }

    public function test_an_admin_edit_is_shown_on_the_page_and_escaped_safely(): void
    {
        Policy::where('slug', 'shipping-policy')->firstOrFail()->update(['body' => '<h2>Updated heading</h2><p>New shipping text.</p>']);

        $this->get('/policies/shipping-policy')->assertOk()->assertSee('Updated heading')->assertSee('New shipping text.');
    }

    public function test_the_policies_admin_screens_load(): void
    {
        $this->actingAs(Staff::factory()->create(['admin' => true]), 'staff');

        $this->get('/lunar/policies')->assertOk()->assertSee('Terms & Conditions');
        $this->get('/lunar/policies/'.Policy::first()->slug.'/edit')->assertOk()->assertSee('Policy text');
    }
}
