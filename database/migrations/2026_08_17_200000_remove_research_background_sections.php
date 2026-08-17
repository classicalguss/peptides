<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 9: remove the Research Background section from
 * commercial product pages. The section now renders only when the field is
 * filled, so clearing it hides the section and its heading; the admin field
 * stays available for the neutral scientific information the client plans
 * to add back later.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_profiles')->update(['research_info' => null]);
    }

    public function down(): void
    {
        // Content removal; the previous wording is not restored.
    }
};
