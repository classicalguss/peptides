<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('lunar_products')->cascadeOnDelete();
            $table->string('kind')->default('compound');
            $table->string('handle')->unique();
            $table->string('accent')->default('gold');
            $table->string('subtitle')->nullable();
            $table->string('tagline')->nullable();
            $table->string('protocol_label')->nullable();
            $table->string('dose')->nullable();
            $table->text('summary')->nullable();
            $table->text('overview')->nullable();
            $table->text('research_info')->nullable();
            $table->text('dosage')->nullable();
            $table->text('storage')->nullable();
            $table->json('benefits')->nullable();
            $table->json('highlights')->nullable();
            $table->json('pillars')->nullable();
            $table->json('audience')->nullable();
            $table->json('faq')->nullable();
            $table->decimal('save_up_to', 5, 1)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['kind', 'position']);
        });

        Schema::create('stack_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('lunar_products')->cascadeOnDelete();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('code');
            $table->string('label');
            $table->unsignedInteger('price');
            $table->unsignedInteger('subscribe_price');
            $table->unsignedInteger('supply_days');
            $table->decimal('save_percent', 5, 1)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'code']);
        });

        Schema::create('stack_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stack_product_id')->constrained('lunar_products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('lunar_products')->cascadeOnDelete();
            $table->unsignedInteger('base_quantity')->default(1);
            $table->string('unit')->default('VIAL');
            $table->string('benefit')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['stack_product_id', 'component_product_id']);
        });

        Schema::create('coa_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('lunar_products')->nullOnDelete();
            $table->string('product_label');
            $table->string('batch_number')->unique();
            $table->date('tested_on');
            $table->string('purity');
            $table->string('lab_name')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('lunar_products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');
            $table->boolean('is_verified')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('coa_reports');
        Schema::dropIfExists('stack_components');
        Schema::dropIfExists('stack_tiers');
        Schema::dropIfExists('product_profiles');
    }
};
