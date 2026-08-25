<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The store's own tables beside Lunar's. Everything the storefront and admin
 * need that Lunar does not provide: editable site copy and lists, the policy
 * pages, contact-form messages, lab certificates, Research Collection
 * contents and sizes, and customer reviews.
 *
 * Each table is only created when missing, so this migration is safe on a
 * database that was built by the earlier, since-consolidated migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_texts')) {
            Schema::create('website_texts', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('page');
                $table->string('section');
                $table->string('label');
                $table->string('location_hint')->nullable();
                $table->string('route_name')->nullable();
                $table->longText('default_value');
                $table->longText('value');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['page', 'section']);
            });
        }

        if (! Schema::hasTable('website_list_items')) {
            Schema::create('website_list_items', function (Blueprint $table) {
                $table->id();
                $table->string('list_key')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('heading')->nullable();
                $table->text('body')->nullable();
                $table->string('extra')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('policies')) {
            Schema::create('policies', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->longText('body');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('topic')->default('general');
                $table->string('order_reference')->nullable();
                $table->text('message');
                $table->timestamp('handled_at')->nullable();
                $table->timestamps();

                $table->index(['handled_at', 'created_at']);
            });
        }

        if (! Schema::hasTable('coa_reports')) {
            Schema::create('coa_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('lunar_products')->nullOnDelete();
                $table->string('product_label');
                $table->string('batch_number')->nullable()->unique();
                $table->date('tested_on')->nullable();
                $table->string('purity')->nullable();
                $table->string('lab_name')->nullable();
                $table->string('pdf_path')->nullable();
                $table->string('status')->default('pass');
                $table->string('status_label')->nullable();
                $table->text('status_note')->nullable();
                $table->string('status_color', 16)->default('gray');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stack_components')) {
            Schema::create('stack_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stack_product_id')->constrained('lunar_products')->cascadeOnDelete();
                $table->foreignId('component_product_id')->constrained('lunar_products')->cascadeOnDelete();
                $table->unsignedInteger('base_quantity')->default(1);
                $table->string('unit')->default('VIAL');
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['stack_product_id', 'component_product_id']);
            });
        }

        if (! Schema::hasTable('stack_tiers')) {
            Schema::create('stack_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('lunar_products')->cascadeOnDelete();
                $table->unsignedBigInteger('product_variant_id')->nullable();
                $table->string('code');
                $table->string('label');
                $table->unsignedInteger('supply_days');
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'code']);
            });
        }

        if (! Schema::hasTable('product_reviews')) {
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
    }

    public function down(): void
    {
        foreach (['product_reviews', 'stack_tiers', 'stack_components', 'coa_reports', 'contact_messages', 'policies', 'website_list_items', 'website_texts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
