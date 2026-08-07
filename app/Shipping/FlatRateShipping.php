<?php

namespace App\Shipping;

use Closure;
use Lunar\Base\ShippingModifier;
use Lunar\DataTypes\Price;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Contracts\Cart;
use Lunar\Models\TaxClass;

/**
 * Lunar ships with no shipping rates out of the box. This modifier publishes
 * flat rates into the manifest whenever a cart is calculated, so checkout can
 * resolve a shipping option and create an order.
 */
class FlatRateShipping extends ShippingModifier
{
    /**
     * Carts at or above this subtotal (in cents) qualify for free standard shipping.
     */
    public const FREE_SHIPPING_THRESHOLD = 20000;

    public const STANDARD_RATE = 1200;

    public const EXPRESS_RATE = 2500;

    public function handle(Cart $cart, Closure $next)
    {
        $taxClass = TaxClass::firstWhere('default', true) ?? TaxClass::first();

        if ($taxClass) {
            $currency = $cart->currency;
            $qualifiesForFree = ($cart->subTotal?->value ?? 0) >= self::FREE_SHIPPING_THRESHOLD;

            ShippingManifest::addOption(new ShippingOption(
                name: $qualifiesForFree ? 'Free Standard Shipping' : 'Standard Shipping',
                description: '3-5 business days, discreet packaging',
                identifier: 'STANDARD',
                price: new Price($qualifiesForFree ? 0 : self::STANDARD_RATE, $currency, 1),
                taxClass: $taxClass,
            ));

            ShippingManifest::addOption(new ShippingOption(
                name: 'Express Shipping',
                description: '1-2 business days, cold chain packed',
                identifier: 'EXPRESS',
                price: new Price(self::EXPRESS_RATE, $currency, 1),
                taxClass: $taxClass,
            ));
        }

        return $next($cart);
    }
}
