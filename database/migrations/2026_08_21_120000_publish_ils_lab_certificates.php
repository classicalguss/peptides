<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * New revision list, items 3 + 4: replace the placeholder/sample COA data
 * with the current ILS Laboratories certificates.
 *
 * Adds a status column (pass | testing | pending) so products without a
 * publishable certificate (NAD+ under retest, BAC Water awaiting documents)
 * can be shown honestly instead of with fabricated batch data. Batch fields
 * become nullable for those states. Rows are matched by their placeholder
 * batch number, which is identical on every environment.
 */
return new class extends Migration
{
    private const PASSING = [
        'BPC157-0524' => ['BPC-157 20mg', 'PUP-BC20-001', '2026-08-13', '99.91%'],
        'TB500-0524' => ['TB-500 20mg', 'PUP-BT20-001', '2026-08-13', '99.70%'],
        'GHKCU-0524' => ['GHK-Cu 100mg', 'PUP-CU100-001', '2026-08-12', '99.53%'],
        'CJCIPA-0524' => ['CJC-1295 / Ipamorelin 20mg', 'PUP-CP20-001', '2026-08-13', '99.40%'],
        'MOTSC-0524' => ['MOTS-C 40mg', 'PUP-MS40-001', '2026-08-13', '98.66%'],
        'RETA15-0524' => ['Retatrutide 15mg', 'PUP-RT15-001', '2026-08-13', '99.02%'],
        'RETA30-0524' => ['Retatrutide 30mg', 'PUP-RT30-001', '2026-08-13', '98.94%'],
        'RETA60-0524' => ['Retatrutide 60mg', 'PUP-RT60-001', '2026-08-13', '98.68%'],
    ];

    public function up(): void
    {
        Schema::table('coa_reports', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->change();
            $table->date('tested_on')->nullable()->change();
            $table->string('purity')->nullable()->change();
            $table->string('status')->default('pass')->after('purity');
        });

        foreach (self::PASSING as $oldBatch => [$label, $batch, $testedOn, $purity]) {
            DB::table('coa_reports')->where('batch_number', $oldBatch)->update([
                'product_label' => $label,
                'batch_number' => $batch,
                'tested_on' => $testedOn,
                'purity' => $purity,
                'lab_name' => 'ILS Laboratories',
                'pdf_path' => 'coa/'.$batch.'.pdf',
                'status' => 'pass',
            ]);
        }

        // NAD+ failed its 08/2026 HPLC run and is being retested; nothing may
        // be displayed for it except the testing-in-progress status.
        DB::table('coa_reports')->where('batch_number', 'NAD1000-0524')->update([
            'product_label' => 'NAD+ 1000mg',
            'batch_number' => null,
            'tested_on' => null,
            'purity' => null,
            'lab_name' => null,
            'pdf_path' => null,
            'status' => 'testing',
        ]);

        // BAC Water's current certificate arrives separately (from Salah);
        // until then the row carries no batch data at all.
        DB::table('coa_reports')->where('batch_number', 'BAC-0524')->update([
            'product_label' => 'BAC Water 10ml',
            'batch_number' => null,
            'tested_on' => null,
            'purity' => null,
            'lab_name' => null,
            'pdf_path' => null,
            'status' => 'pending',
        ]);

        $this->syncWebsiteText();

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        Schema::table('coa_reports', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // The placeholder certificate data is intentionally not restored.
    }

    /**
     * Registers the new testing-status strings and replaces the two labels
     * that referred to placeholder certificates ("Pending Upload" and the
     * sample-data table note) with the current defaults.
     */
    private function syncWebsiteText(): void
    {
        $now = now();

        foreach (['labs.pending_label', 'labs.testing_label', 'labs.testing_note', 'labs.table_note'] as $key) {
            $definition = config('website-text', [])[$key] ?? null;

            if ($definition === null) {
                continue;
            }

            $attributes = [
                'page' => $definition['page'],
                'section' => $definition['section'],
                'label' => $definition['label'],
                'location_hint' => $definition['location_hint'] ?? null,
                'route_name' => $definition['route_name'] ?? null,
                'default_value' => $definition['default'],
                'value' => $definition['default'],
                'sort_order' => $definition['sort_order'] ?? 0,
                'updated_at' => $now,
            ];

            if (DB::table('website_texts')->where('key', $key)->exists()) {
                DB::table('website_texts')->where('key', $key)->update($attributes);
            } else {
                DB::table('website_texts')->insert([
                    ...$attributes,
                    'key' => $key,
                    'created_at' => $now,
                ]);
            }
        }
    }
};
