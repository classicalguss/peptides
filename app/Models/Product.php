<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Lunar\Base\FieldType;
use Lunar\Models\Product as LunarProduct;
use Lunar\Models\ProductType;

/**
 * The store's product: Lunar's model plus the storefront helpers that read
 * the "Website Page" attributes and the collection (stack) relations.
 * Registered over Lunar's model via ModelManifest in AppServiceProvider.
 */
class Product extends LunarProduct
{
    public const TYPE_COMPOUND = 'Research Compound';

    public const TYPE_COLLECTION = 'Research Collection';

    public function tiers(): HasMany
    {
        return $this->hasMany(StackTier::class, 'product_id')->orderBy('position');
    }

    public function components(): HasMany
    {
        return $this->hasMany(StackComponent::class, 'stack_product_id')->orderBy('position');
    }

    public function coa(): HasOne
    {
        return $this->hasOne(CoaReport::class, 'product_id');
    }

    /**
     * A Research Collection (several compounds sold together in sizes).
     */
    public function isStack(): bool
    {
        return $this->product_type_id === static::typeId(self::TYPE_COLLECTION);
    }

    /**
     * Laboratory supplies (bacteriostatic water) are sold like compounds but
     * are liquids, so they carry their own checkmark lines.
     */
    public function isSupply(): bool
    {
        return in_array($this->id, Catalog::productIdsInCategory('supplies'), true);
    }

    /**
     * The id of a product type by name, looked up once per request.
     */
    public static function typeId(string $name): ?int
    {
        static $ids = [];

        if (! array_key_exists($name, $ids)) {
            $ids[$name] = ProductType::query()->where('name', $name)->value('id');
        }

        return $ids[$name];
    }

    public function slug(): ?string
    {
        return $this->urls->first()?->slug;
    }

    public function storefrontUrl(): ?string
    {
        $slug = $this->slug();

        return $slug === null ? null : route($this->isStack() ? 'stack' : 'compound', $slug);
    }

    /**
     * A single-value "Website Page" attribute as a string, or null when empty.
     */
    public function pageText(string $handle): ?string
    {
        $value = $this->pageValue($handle);

        if ($value === null || is_array($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * A list-type "Website Page" attribute.
     *
     * @return array<int, string>
     */
    public function pageList(string $handle): array
    {
        $value = $this->pageValue($handle);

        return is_array($value) ? array_values($value) : [];
    }

    public function displayOrder(): int
    {
        return (int) ($this->pageValue('display_order') ?? 0);
    }

    protected function pageValue(string $handle): mixed
    {
        $field = $this->attribute_data?->get($handle);

        return $field instanceof FieldType ? $field->getValue() : null;
    }

    protected function subtitle(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('subtitle'));
    }

    protected function tagline(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('tagline'));
    }

    protected function protocolLabel(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('protocol_label'));
    }

    protected function dose(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('dose'));
    }

    protected function summary(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('summary'));
    }

    protected function overview(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('overview'));
    }

    protected function researchInfo(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('research_info'));
    }

    protected function storage(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('storage'));
    }

    protected function highlights(): Attribute
    {
        return Attribute::get(fn () => $this->pageList('highlights'));
    }

    protected function pillars(): Attribute
    {
        return Attribute::get(fn () => $this->pageList('pillars'));
    }

    protected function accent(): Attribute
    {
        return Attribute::get(fn () => $this->pageText('accent') ?? 'gold');
    }

    public function accentHex(): string
    {
        return config("theme.accents.{$this->accent}.hex", config('theme.brand.gold'));
    }

    public function accentGlow(): string
    {
        return config("theme.accents.{$this->accent}.glow", config('theme.brand.gold_deep'));
    }

    /**
     * Inline CSS custom properties consumed by the accent-* utilities.
     */
    public function accentStyle(): string
    {
        return "--accent: {$this->accentHex()}; --accent-glow: {$this->accentGlow()};";
    }
}
