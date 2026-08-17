<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('website_texts')
            ->where('key', 'global.nav_collection_cta')
            ->delete();
    }

    public function down(): void
    {
        DB::table('website_texts')->insertOrIgnore([
            'key' => 'global.nav_collection_cta',
            'page' => 'Shared Site Content',
            'section' => 'Main Menu',
            'label' => 'Collections shortcut button',
            'location_hint' => 'Gold outlined shortcut in the main navigation.',
            'route_name' => 'stacks',
            'default_value' => 'Shop Stacks',
            'value' => 'Shop Stacks',
            'sort_order' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
