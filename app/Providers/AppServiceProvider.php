<?php

namespace App\Providers;

use App\Filament\Extensions\EditProductPageExtension;
use App\Filament\Extensions\ProductResourceExtension;
use App\Filament\Resources\CoaReportResource;
use App\Filament\Resources\ProductTextSearchResource;
use App\Filament\Resources\WebsiteTextResource;
use App\Models\ProductProfile;
use App\Shipping\FlatRateShipping;
use Filament\Support\Colors\Color;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\EditProduct;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Base\ShippingModifiers;
use Lunar\Models\Product;

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
                ->resources([
                    WebsiteTextResource::class,
                    CoaReportResource::class,
                    ProductTextSearchResource::class,
                ])
                ->navigationGroups([
                    'Website',
                ])
        )->extensions([
            ProductResource::class => ProductResourceExtension::class,
            EditProduct::class => EditProductPageExtension::class,
        ])->register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::resolveRelationUsing(
            'profile',
            fn (Product $product) => $product->hasOne(ProductProfile::class)
        );

        $this->app->make(ShippingModifiers::class)->add(FlatRateShipping::class);
    }
}
