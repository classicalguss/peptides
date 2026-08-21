<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Collection tier prices, the unused "scheduled reorder" price, and the
 * stored savings percentages were copies of numbers that Lunar already
 * owns (variant prices). The storefront now reads prices from the Lunar
 * variant and derives savings from them, so the copies go.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stack_tiers', function (Blueprint $table) {
            $table->dropColumn(['price', 'subscribe_price', 'save_percent']);
        });

        Schema::table('product_profiles', function (Blueprint $table) {
            $table->dropColumn('save_up_to');
        });
    }

    public function down(): void
    {
        Schema::table('stack_tiers', function (Blueprint $table) {
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('subscribe_price')->default(0);
            $table->decimal('save_percent', 5, 1)->default(0);
        });

        Schema::table('product_profiles', function (Blueprint $table) {
            $table->decimal('save_up_to', 5, 1)->nullable();
        });
    }
};
