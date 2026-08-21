<?php

namespace App\Providers;

use App\Filament\Clusters\WebsiteLists\Pages\AboutCommitmentsList;
use App\Filament\Clusters\WebsiteLists\Pages\AboutProcessList;
use App\Filament\Clusters\WebsiteLists\Pages\AboutStatsList;
use App\Filament\Clusters\WebsiteLists\Pages\CartTrustList;
use App\Filament\Clusters\WebsiteLists\Pages\CollectionProductTrustList;
use App\Filament\Clusters\WebsiteLists\Pages\CompoundProductTrustList;
use App\Filament\Clusters\WebsiteLists\Pages\ConfirmationStepsList;
use App\Filament\Clusters\WebsiteLists\Pages\ContactFaqList;
use App\Filament\Clusters\WebsiteLists\Pages\ContactWriteTipsList;
use App\Filament\Clusters\WebsiteLists\Pages\GlobalTrustList;
use App\Filament\Clusters\WebsiteLists\Pages\HomeWhyList;
use App\Filament\Clusters\WebsiteLists\Pages\LabsHowList;
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
                ->favicon(asset('favicon-32x32.png'))
                ->colors([
                    'primary' => Color::Amber,
                ])
                ->resources([
                    WebsiteTextResource::class,
                    CoaReportResource::class,
                    ProductTextSearchResource::class,
                ])
                ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
                ->pages([
                    GlobalTrustList::class,
                    AboutStatsList::class,
                    AboutCommitmentsList::class,
                    AboutProcessList::class,
                    HomeWhyList::class,
                    LabsHowList::class,
                    ContactFaqList::class,
                    ContactWriteTipsList::class,
                    CartTrustList::class,
                    CompoundProductTrustList::class,
                    CollectionProductTrustList::class,
                    ConfirmationStepsList::class,
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
