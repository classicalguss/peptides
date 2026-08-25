<?php

namespace App\Filament\Extensions;

use App\Filament\Support\Pages\ManageVariantPricing;
use Lunar\Admin\Filament\Resources\ProductVariantResource\Pages\ManageVariantPricing as LunarManageVariantPricing;
use Lunar\Admin\Support\Extending\ResourceExtension;

/**
 * Swaps Lunar's variant pricing page for ours, which fixes the cent the
 * quantity break tiers used to lose. See {@see ManageVariantPricing}.
 */
class ProductVariantResourceExtension extends ResourceExtension
{
    /**
     * @param  array<string, mixed>  $pages
     * @return array<string, mixed>
     */
    public function extendPages(array $pages): array
    {
        $pages['pricing'] = ManageVariantPricing::route('/{record}/pricing');

        return $pages;
    }

    /**
     * @param  array<int, class-string>  $pages
     * @return array<int, class-string>
     */
    public function extendSubNavigation(array $pages): array
    {
        return array_map(
            fn (string $page): string => $page === LunarManageVariantPricing::class
                ? ManageVariantPricing::class
                : $page,
            $pages
        );
    }
}
