<?php

namespace App\Providers;

use App\Shipping\FlatRateShipping;
use Filament\Support\Colors\Color;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Base\ShippingModifiers;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        LunarPanel::panel(
            fn ($panel) => $panel
                ->brandName('Powered Up Peptides')
                ->colors([
                    'primary' => Color::Amber,
                ])
        )->register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make(ShippingModifiers::class)->add(FlatRateShipping::class);
    }
}
