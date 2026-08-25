<?php

use App\Models\Product;
use App\Support\WebsitePageAttributes;
use Illuminate\Database\Migrations\Migration;
use Lunar\Models\ProductType;

/**
 * The two product types the storefront understands and the "Website Page"
 * attribute group each of them carries. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([Product::TYPE_COMPOUND, Product::TYPE_COLLECTION] as $name) {
            ProductType::query()->firstOrCreate(['name' => $name]);
        }

        WebsitePageAttributes::ensure();
    }

    public function down(): void
    {
        // Attributes and product types are left in place: products depend on them.
    }
};
