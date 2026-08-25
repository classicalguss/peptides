---
paths:
  - 'app/Filament/**'
---

# Filament

## Lunar admin pricing tables truncate cents — use our overrides
Lunar's pricing tables convert entered prices with `(int) ($price * $factor)`. Floating point makes "79.99" * 100 = 7998.999…, so the cast stores $79.98 (only some values: 79.99, 69.99, 19.99…). Lunar's base price form rounds and is fine; the tiered and customer-group tables do not.

Fixed by `App\Filament\Support\Concerns\RoundsEnteredPrices`, which replaces the create/edit `mutateFormDataUsing` with a rounding version (it also scales `compare_price`, which Lunar's tiered table forgets to convert at all).

Lunar's `PriceRelationManager` overrides `table()` directly, so it has no `extendTable` hook — that is why we subclass it and swap the pricing pages in via the `extendPages` / `extendSubNavigation` / `getRelations` extension hooks (see `ProductResourceExtension`, `ProductVariantResourceExtension`). The customer-group manager does use `getDefaultTable()`, so a `RelationManagerExtension` is enough there. Re-check these after a Lunar upgrade; tests/Feature/AdminTierPricingTest.php covers them.
