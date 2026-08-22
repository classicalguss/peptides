<?php

use App\Support\WebsiteContent;
use Illuminate\Database\Migrations\Migration;

/**
 * Registers every editable text key, list and policy page from config and
 * the data files. Re-run (by a new migration calling the same method) when
 * keys are added; existing admin-edited values are never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        WebsiteContent::sync();
    }

    public function down(): void
    {
        // Content is data, not schema; nothing to undo.
    }
};
