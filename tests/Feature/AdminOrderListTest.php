<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\ListOrders;
use Lunar\Admin\Models\Staff;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Tests\TestCase;

class AdminOrderListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);
        Channel::factory()->create(['default' => true]);

        $this->actingAs(Staff::factory()->create(['admin' => true]), 'staff');
    }

    private function order(array $attributes = []): Order
    {
        return Order::factory()->create(array_merge([
            'currency_code' => 'USD',
            'status' => 'awaiting-payment',
            'placed_at' => null,
            'meta' => [],
        ], $attributes));
    }

    public function test_orders_are_listed_by_default_whether_or_not_they_have_been_paid(): void
    {
        $unpaid = $this->order();
        $paid = $this->order(['status' => 'payment-received', 'placed_at' => now()]);
        $old = $this->order(['created_at' => now()->subYears(2)]);

        Livewire::test(ListOrders::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$unpaid, $paid, $old])
            ->assertCountTableRecords(3);
    }

    public function test_the_date_filter_uses_the_date_the_order_was_created(): void
    {
        $recentUnpaid = $this->order();
        $old = $this->order(['created_at' => now()->subYear(), 'placed_at' => now()]);

        Livewire::test(ListOrders::class)
            ->loadTable()
            ->filterTable('created_at', ['created_after' => now()->subMonth()->toDateString()])
            ->assertCanSeeTableRecords([$recentUnpaid])
            ->assertCanNotSeeTableRecords([$old])
            ->removeTableFilter('created_at')
            ->filterTable('created_at', ['created_before' => now()->subMonth()->toDateString()])
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$recentUnpaid]);
    }

    public function test_the_order_list_shows_a_date_for_unpaid_orders(): void
    {
        $unpaid = $this->order(['created_at' => '2026-09-15 10:00:00']);

        Livewire::test(ListOrders::class)
            ->loadTable()
            ->assertTableColumnExists('created_at')
            ->assertTableColumnStateSet('created_at', $unpaid->created_at, $unpaid);
    }
}
