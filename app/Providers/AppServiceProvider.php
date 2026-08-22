<?php

namespace App\Providers;

use App\FieldTypes\Textarea;
use App\FieldTypes\TextList;
use App\Filament\Extensions\EditProductPageExtension;
use App\Filament\Extensions\ProductResourceExtension;
use App\Filament\FieldTypes\TextareaField;
use App\Filament\FieldTypes\TextListField;
use App\Filament\Resources\CoaReportResource;
use App\Filament\Resources\PolicyResource;
use App\Filament\Resources\ProductTextSearchResource;
use App\Filament\Resources\WebsiteTextResource;
use App\Models\Product;
use App\Shipping\FlatRateShipping;
use Filament\Support\Colors\Color;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\EditProduct;
use Lunar\Admin\Support\Facades\AttributeData;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Base\FieldTypeManifestInterface;
use Lunar\Base\ShippingModifiers;
use Lunar\Facades\ModelManifest;
use Lunar\Models\Contracts\Product as ProductContract;

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
                    PolicyResource::class,
                    ProductTextSearchResource::class,
                ])
                ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
                ->navigationGroups([
                    'Website',
                ])
        )->extensions([
            ProductResource::class => ProductResourceExtension::class,
            EditProduct::class => EditProductPageExtension::class,
        ])->register();

        // Storefront copy is edited as Lunar attributes; these are the two
        // field types the "Website Page" group needs beyond Lunar's own.
        AttributeData::registerFieldType(Textarea::class, TextareaField::class);
        AttributeData::registerFieldType(TextList::class, TextListField::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ModelManifest::replace(ProductContract::class, Product::class);

        $fieldTypes = $this->app->make(FieldTypeManifestInterface::class);
        $fieldTypes->add(Textarea::class);
        $fieldTypes->add(TextList::class);

        $this->app->make(ShippingModifiers::class)->add(FlatRateShipping::class);
    }
}
