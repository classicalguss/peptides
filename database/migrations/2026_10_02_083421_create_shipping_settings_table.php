<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row table holding the ship-from address and default parcel used
 * when buying EasyPost labels, so staff can edit them in the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->string('from_name')->nullable();
            $table->string('from_company')->nullable();
            $table->string('from_street1')->nullable();
            $table->string('from_street2')->nullable();
            $table->string('from_city')->nullable();
            $table->string('from_state')->nullable();
            $table->string('from_zip')->nullable();
            $table->string('from_phone')->nullable();
            $table->decimal('parcel_weight_oz', 8, 2)->nullable();
            $table->decimal('parcel_length', 8, 2)->nullable();
            $table->decimal('parcel_width', 8, 2)->nullable();
            $table->decimal('parcel_height', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_settings');
    }
};
