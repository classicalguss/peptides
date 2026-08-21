<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Policy / legal pages, seeded with the client's finalized wording from
 * database/data/policies/*.html (converted verbatim from their document).
 * Editable afterwards in the admin.
 */
return new class extends Migration
{
    private const POLICIES = [
        ['slug' => 'terms-and-conditions', 'title' => 'Terms & Conditions'],
        ['slug' => 'privacy-policy', 'title' => 'Privacy Policy'],
        ['slug' => 'shipping-policy', 'title' => 'Shipping Policy'],
        ['slug' => 'return-and-refund-policy', 'title' => 'Return & Refund Policy'],
        ['slug' => 'research-use-only-policy', 'title' => 'Research Use Only (RUO) Policy'],
    ];

    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        foreach (self::POLICIES as $index => $policy) {
            DB::table('policies')->insert([
                ...$policy,
                'body' => file_get_contents(database_path("data/policies/{$policy['slug']}.html")),
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Cache::forget('policies.nav');
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
        Cache::forget('policies.nav');
    }
};
