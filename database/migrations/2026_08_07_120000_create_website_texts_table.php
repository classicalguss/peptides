<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        $now = now();

        foreach (config('website-text', []) as $key => $definition) {
            DB::table('website_texts')->insert([
                'key' => $key,
                'page' => $definition['page'],
                'section' => $definition['section'],
                'label' => $definition['label'],
                'location_hint' => $definition['location_hint'] ?? null,
                'route_name' => $definition['route_name'] ?? null,
                'default_value' => $definition['default'],
                'value' => $definition['default'],
                'sort_order' => $definition['sort_order'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('website_texts');
    }
};
