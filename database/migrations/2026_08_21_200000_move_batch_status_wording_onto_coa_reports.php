<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revision item 13: a batch is either published (pass) or not, and when it
 * is not, the wording and colour shown in its place belong to the batch
 * record itself — one admin screen per product — instead of global website
 * text keys. Existing testing/pending rows keep their current wording.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coa_reports', function (Blueprint $table) {
            $table->string('status_label')->nullable()->after('status');
            $table->text('status_note')->nullable()->after('status_label');
            $table->string('status_color', 16)->default('gray')->after('status_note');
        });

        $text = DB::table('website_texts')->pluck('value', 'key');

        $presets = [
            'testing' => ['label' => $text['labs.testing_label'] ?? 'Additional Testing in Progress', 'note' => $text['labs.testing_note'] ?? null, 'color' => 'yellow'],
            'pending' => ['label' => $text['labs.pending_label'] ?? 'Documentation Pending', 'note' => null, 'color' => 'gray'],
            'fail' => ['label' => 'Did Not Pass', 'note' => null, 'color' => 'red'],
        ];

        foreach ($presets as $status => $preset) {
            DB::table('coa_reports')->where('status', $status)->update([
                'status' => 'unpublished',
                'status_label' => $preset['label'],
                'status_note' => $preset['note'],
                'status_color' => $preset['color'],
            ]);
        }

        DB::table('website_texts')
            ->whereIn('key', ['labs.testing_label', 'labs.testing_note', 'labs.pending_label'])
            ->delete();

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        Schema::table('coa_reports', function (Blueprint $table) {
            $table->dropColumn(['status_label', 'status_note', 'status_color']);
        });
    }
};
