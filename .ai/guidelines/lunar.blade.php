# Lunar E-Commerce (lunarphp/lunar v1.3)

This app is built on Lunar, a Laravel e-commerce package. Boost's `search-docs` tool does NOT
cover Lunar — for Lunar API questions, use the context7 MCP docs (`/lunarphp/docs`) or
https://docs.lunarphp.io. Confirm APIs against the installed 1.x version.

## Domain Model

- Core commerce models live in `Lunar\Models\*` (Product, ProductVariant, Price, Collection,
  Cart, CartLine, Order, Customer, Currency, TaxClass). Do not create parallel app models for
  concepts Lunar already provides.
- All money is stored in **integer minor units (cents)**. Lunar's `Price` datatype exposes
  `->value` (int cents), `->decimal`, and `->formatted`. Never store or compare floats.
- Variant prices support quantity-tier pricing via the `min_quantity` column on `prices`
  (the storefront sorts `$variant->prices` by `min_quantity` to build tier tables).
- App-specific product data hangs off Lunar's Product via relations, NOT by modifying Lunar:
  - `ProductProfile` — attached as a dynamic `profile` relation via
    `Product::resolveRelationUsing()` in `AppServiceProvider::boot()`.
  - `CoaReport`, `ProductReview`, `StackComponent`, `StackTier` — belong-to Product/Variant.
  Follow this pattern for any new product-adjacent data.
- `App\Models\User` uses the `Lunar\Base\Traits\LunarUser` trait, which links users to
  Lunar `Customer` records. Keep customer data on Customer, auth data on User.

## Cart & Checkout Flow

- ALWAYS mutate carts through the `Lunar\Facades\CartSession` facade — never create Cart/CartLine
  rows directly: `CartSession::current()`, `::add($variant, $qty)`, `::updateLine($lineId, $qty)`,
  `::remove($lineId)`, `::clear()`, and `::forget()` (after order placement).
- When acting on a cart line from request input, verify ownership first:
  `abort_if($line->cart_id !== CartSession::current()?->id, 403);`
- Shipping options come from `Lunar\Facades\ShippingManifest` (`getOptions($cart)`,
  `getOption($cart, $identifier)`). Options are published at cart-calculation time by
  `App\Shipping\FlatRateShipping`, a `ShippingModifier` registered in `AppServiceProvider::boot()`.
  Rates and the free-shipping threshold are integer-cent constants on that class.
- Orders are created from the cart with `$cart->createOrder()` — do not build Order rows manually.
- Payments use Lunar's `offline` driver (`config/lunar/payments.php`, default type
  `cash-in-hand` via `PAYMENTS_TYPE`). There is no card gateway integration; don't assume one.
- Lunar config lives in `config/lunar/*.php` (cart, pricing, orders, shipping, taxes, etc.).

## Admin Panel (Lunar Admin = Filament 3)

- The admin panel is Lunar's Filament panel, configured via `LunarPanel::panel(...)` in
  `AppServiceProvider::register()`. Do NOT create a separate Filament panel provider.
- Filament is a transitive dependency of Lunar — match Filament v3 APIs, not v4.
- To change Lunar's own admin screens, use Lunar's extension system
  (`Lunar\Admin\Support\Extending\*`), mapped in the `->extensions([...])` call:
  `ProductResourceExtension` (ResourceExtension) and `EditProductPageExtension`
  (EditPageExtension) are existing examples — follow them.
- App-specific admin resources (e.g. `WebsiteTextResource`) are plain Filament resources in
  `app/Filament/Resources`, registered into the Lunar panel via `->resources([...])` and grouped
  under the `Website` navigation group.

## Search

- Lunar models are Scout-searchable; the app uses Scout's `collection` driver (no external
  search engine is configured). Prefer `Model::search()` for product searching over raw
  `LIKE` queries. Per-model driver overrides live in `config/lunar/search.php`.
