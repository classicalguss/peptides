<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repeating content (FAQ entries, trust promises, process steps, checkmark
 * bullets) moves out of fixed numbered website_texts keys into a proper
 * list table the admin can add to, remove from and reorder.
 *
 * Items are carried over from the live website_texts values so admin edits
 * survive. Only the specific fields listed under a list's `overrides` are
 * replaced — the client's new wording from revision items 8 and 9 for the
 * About commitments and process steps. The numbered keys are then removed
 * from website_texts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('list_key')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('heading')->nullable();
            $table->text('body')->nullable();
            $table->string('extra')->nullable();
            $table->timestamps();
        });

        $now = now();
        $legacyKeys = [];

        foreach (config('website-lists', []) as $listKey => $definition) {
            foreach ($definition['defaults'] as $index => $default) {
                $position = $index + 1;
                $row = ['list_key' => $listKey, 'sort_order' => $position, 'created_at' => $now, 'updated_at' => $now];

                foreach (['heading', 'body', 'extra'] as $field) {
                    $value = $default[$field] ?? null;
                    $legacyKey = isset($definition['legacy_keys'][$field])
                        ? str_replace('{i}', (string) $position, $definition['legacy_keys'][$field])
                        : null;

                    if ($legacyKey !== null) {
                        $live = DB::table('website_texts')->where('key', $legacyKey)->value('value');

                        if ($live !== null) {
                            $value = $live;
                        }
                    }

                    $row[$field] = $definition['overrides'][$position][$field] ?? $value;
                }

                DB::table('website_list_items')->insert($row);
            }

            // Remove every numbered key for this list, including any beyond the
            // seeded count.
            foreach ($definition['legacy_keys'] as $pattern) {
                for ($i = 1; $i <= 20; $i++) {
                    $legacyKeys[] = str_replace('{i}', (string) $i, $pattern);
                }
            }
        }

        DB::table('website_texts')->whereIn('key', $legacyKeys)->delete();

        Cache::forget('website-text.values');
        Cache::forget('website-list.items');
    }

    public function down(): void
    {
        Schema::dropIfExists('website_list_items');

        // The numbered website_texts keys are not recreated.
    }
};
