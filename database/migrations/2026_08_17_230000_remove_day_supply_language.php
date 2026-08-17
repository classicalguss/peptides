<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 11: remove day-supply language. Collection
 * quantities should read as neutral collection sizes, not a predetermined
 * duration of use. The client's own example replacement ("HP Core / Z Plus
 * / S Max") is applied here — it is the identical rename requested
 * separately in revision item 32 (Beginner/Intermediate/Advanced), so it is
 * done once rather than twice. supply_days is NOT cleared: it still drives
 * the What's Included vial-quantity multiplier, only its "-day supply"
 * display text is removed (see stack.blade.php / stacks.blade.php).
 */
return new class extends Migration
{
    private const LABELS = [
        'Beginner' => 'Core',
        'Intermediate' => 'Plus',
        'Advanced' => 'Max',
    ];

    public function up(): void
    {
        foreach (self::LABELS as $old => $new) {
            DB::table('stack_tiers')->where('label', $old)->update(['label' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::LABELS as $old => $new) {
            DB::table('stack_tiers')->where('label', $new)->update(['label' => $old]);
        }
    }
};
