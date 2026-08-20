<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Publishes the BAC Water certificate provided by Salah (approved by the
 * client): Freedom Diagnostics, lot 21310001, reported 07/20/2026 —
 * HPLC-UV purity 99.94%, identity confirmed (benzyl alcohol), endotoxin
 * and microbial PCR both passing. Replaces the documentation-pending
 * state from the ILS publication migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('coa_reports')->where('product_label', 'BAC Water 10ml')->update([
            'batch_number' => '21310001',
            'tested_on' => '2026-07-20',
            'purity' => '99.94%',
            'lab_name' => 'Freedom Diagnostics',
            'pdf_path' => 'coa/BAC10-21310001.pdf',
            'status' => 'pass',
        ]);
    }

    public function down(): void
    {
        DB::table('coa_reports')->where('product_label', 'BAC Water 10ml')->update([
            'batch_number' => null,
            'tested_on' => null,
            'purity' => null,
            'lab_name' => null,
            'pdf_path' => null,
            'status' => 'pending',
        ]);
    }
};
