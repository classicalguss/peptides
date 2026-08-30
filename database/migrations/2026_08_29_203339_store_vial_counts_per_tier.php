<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vial counts become an explicit number per compound per collection size,
 * replacing the old "base quantity × size multiplier" rule. Sizes no longer
 * have to scale every compound uniformly (e.g. Z can hold 3 Reta but only
 * 7 MOTS-c). Existing counts are backfilled from the old rule so nothing
 * changes visually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stack_tier_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stack_tier_id')->constrained('stack_tiers')->cascadeOnDelete();
            $table->foreignId('stack_component_id')->constrained('stack_components')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['stack_tier_id', 'stack_component_id']);
        });

        $tiersByProduct = DB::table('stack_tiers')->get()->groupBy('product_id');

        foreach (DB::table('stack_components')->get() as $component) {
            foreach ($tiersByProduct->get($component->stack_product_id, collect()) as $tier) {
                DB::table('stack_tier_quantities')->insert([
                    'stack_tier_id' => $tier->id,
                    'stack_component_id' => $component->id,
                    'quantity' => $component->base_quantity * max(1, (int) round($tier->supply_days / 40)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('stack_components', function (Blueprint $table) {
            $table->dropColumn('base_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stack_components', function (Blueprint $table) {
            $table->unsignedInteger('base_quantity')->default(1);
        });

        $smallestTiers = DB::table('stack_tiers')
            ->orderBy('position')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($tiers) => $tiers->first());

        foreach (DB::table('stack_components')->get() as $component) {
            $tier = $smallestTiers->get($component->stack_product_id);

            $quantity = $tier
                ? DB::table('stack_tier_quantities')
                    ->where('stack_tier_id', $tier->id)
                    ->where('stack_component_id', $component->id)
                    ->value('quantity')
                : null;

            DB::table('stack_components')
                ->where('id', $component->id)
                ->update(['base_quantity' => max(1, (int) ($quantity ?? 1))]);
        }

        Schema::dropIfExists('stack_tier_quantities');
    }
};
