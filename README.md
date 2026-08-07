# Powered Up Peptides

A custom Laravel + Lunar e-commerce storefront for research-grade peptides and stack protocols.

## Stack

- **PHP** 8.3 (Homebrew path: `/opt/homebrew/opt/php@8.3/bin`)
- **Laravel** 12.64
- **Lunar** 1.3 (headless e-commerce core + Filament admin)
- **SQLite** for development
- **Tailwind CSS 4** with Vite
- **Spatie Media Library** for product images

## Quick start

```bash
# Make sure PHP 8.3 is on PATH
export PATH="/opt/homebrew/opt/php@8.3/bin:$PATH"

# Install dependencies
composer install
npm install

# Build assets
npm run build

# Create the database and run migrations
php artisan migrate

# Seed the catalog
php artisan db:seed --class=CatalogSeeder

# Start the dev server
php artisan serve --host=127.0.0.1 --port=8100
```

Open `http://localhost:8100`.

> **Port note:** do not use 8123. That is ClickHouse's default HTTP port, and a
> `clickhouse-server` container publishes it on the IPv6 wildcard `[::]:8123`.
> Because macOS resolves `localhost` to `::1` first, `localhost:8123` reaches
> ClickHouse instead of Laravel and returns a bare `Ok.` response.
>
> Binding Laravel to `127.0.0.1` is deliberate: it keeps the dev server on the
> loopback interface only, and `localhost` still works because the IPv6 attempt
> is refused instantly and the client falls back to IPv4.

## Logins

| Panel | URL | User | Password |
|-------|-----|------|----------|
| Storefront account | `/login` | `customer@poweredup.test` | `password123` |
| Storefront account | `/login` | `marie@lab.test` | `radium2024` |
| Lunar admin | `/lunar` | `admin@poweredup.test` | `password123` |

## Architecture

### Catalog data

The catalog lives in `database/data/` and is imported by `database/seeders/CatalogSeeder.php`:

- `compounds.php` — individual research compounds
- `stacks.php` — stack protocols and their tier multipliers
- `lab.php` — categories, batch COAs, and reviews

The seeder is idempotent: it clears the existing catalog before re-importing, so it is safe to re-run.

### Lunar vs custom tables

| Concept | Source |
|---------|--------|
| Products, variants, prices, collections, URLs | Lunar |
| Product profiles, stack tiers, stack components, COAs, reviews | Custom tables (`product_profiles`, `stack_tiers`, `stack_components`, `coa_reports`, `product_reviews`) |
| Orders, carts, customers, shipping, addresses | Lunar |

### Media

Product images are in `public/assets/products/`. Lunar normally letterboxes images on a white background, which destroys the dark vial renders. The project uses `App\Media\PeptideMediaDefinitions` with transparent/crop `Fit::Max` conversions.

### Theme

The design system is defined in `config/theme.php` and the Tailwind utilities in `resources/css/app.css`.

- Black base, gold foil type, electric glows
- Per-stack accent colours injected via CSS custom properties
- Reusable utility classes: `.display-title`, `.text-foil`, `.bg-electric`, `.panel`, `.accent-ring`

## Storefront routes

| Route | Path | Description |
|-------|------|-------------|
| Home | `/` | Hero, featured stack, protocols, compounds, reviews |
| Shop | `/shop?category=...&sort=...` | Filterable grid with price/name sorting |
| Stack | `/stacks/{slug}` | Protocol detail with tier selector |
| Compound | `/peptides/{slug}` | Quantity-break pricing, research info, COA |
| Lab reports | `/lab-reports?batch=...` | Batch / certificate lookup |
| Cart | `/cart` | Update quantities, remove, clear |
| Checkout | `/checkout` | Shipping, address, compliance |
| Confirmation | `/checkout/confirmation/{reference}` | Order summary |
| Account | `/account` | Orders, profile, password |
| Login / Register | `/login`, `/register` | Laravel auth linked to Lunar customer |

## Cart and checkout

The cart uses Lunar's `CartSession` facade:

```php
CartSession::add($variant, $qty);
CartSession::updateLine($lineId, $qty);
CartSession::remove($lineId);
CartSession::clear();
```

Shipping is provided by `App\Shipping\FlatRateShipping`, a `Lunar\Base\ShippingModifier` that publishes flat rates when the cart is calculated:

- **Standard**: $12 (free over $200)
- **Express**: $25

Checkout creates a Lunar order with status `awaiting-payment`.

## Known notes

- Orders are created without payment. A payment gateway must be wired into `CheckoutController::store` or the admin order flow.
- Pricing in the seed data is a first-pass approximation. **Shredd Protocol currently costs more than its components when priced from real per-vial rates.** The UI hides "save" badges for bundles that are not actually cheaper.
- The contact page is a placeholder (`/contact`).
- Mobile navigation uses a bottom-category scroll; the full mobile menu has not been built.

## Handy commands

```bash
# Rebuild the catalog and media
export PATH="/opt/homebrew/opt/php@8.3/bin:$PATH"
php artisan db:seed --class=CatalogSeeder --force
npm run build

# Regenerate product thumbnails
php artisan tinker --execute='\Lunar\Models\Product::get()->each(fn($p) => $p->media->each->update([]));'

# Routes
php artisan route:list

# See what is holding a port before starting the server
lsof -nP -iTCP:8100 -sTCP:LISTEN
