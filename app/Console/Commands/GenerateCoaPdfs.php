<?php

namespace App\Console\Commands;

use App\Models\CoaReport;
use App\Support\SimplePdf;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateCoaPdfs extends Command
{
    protected $signature = 'coa:generate {--force : Rewrite PDFs that already exist}';

    protected $description = 'Generate a placeholder certificate of analysis PDF for every COA record';

    /*
     * Must not collide with a route path. `lab-reports` cannot be used here:
     * the web server would serve this directory instead of the /lab-reports
     * page and the route would 404.
     */
    protected const DIR = 'coa';

    public function handle(): int
    {
        $directory = public_path(self::DIR);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $reports = CoaReport::with('product')->orderBy('product_label')->get();

        if ($reports->isEmpty()) {
            $this->warn('No COA records found. Run the catalog seeder first.');

            return self::FAILURE;
        }

        $written = 0;

        foreach ($reports as $report) {
            $file = Str::lower($report->batch_number).'.pdf';
            $path = $directory.'/'.$file;

            if (file_exists($path) && ! $this->option('force')) {
                $this->line("  skip  {$file}");
            } else {
                file_put_contents($path, $this->build($report));
                $written++;
                $this->line("  write {$file}");
            }

            $report->update(['pdf_path' => self::DIR.'/'.$file]);
        }

        $this->newLine();
        $this->info("{$written} PDF(s) written, {$reports->count()} record(s) linked.");

        return self::SUCCESS;
    }

    protected function build(CoaReport $report): string
    {
        $pdf = new SimplePdf;
        $gold = 'C89B00';
        $grey = '666666';
        $left = 56;

        // Header band
        $pdf->rect(0, 0, 595.28, 92, '0B0A0F');
        $pdf->text('POWERED UP PEPTIDES', $left, 44, 19, true, 'FFC000');
        $pdf->text('Certificate of Analysis', $left, 66, 11, false, 'FFFFFF');
        $pdf->text('RESEARCH USE ONLY', 400, 66, 9, true, 'FFC000');

        $y = 140;
        $pdf->text('BATCH', $left, $y, 8, true, $grey);
        $pdf->text($report->batch_number, $left, $y + 20, 17, true);

        $pdf->text('STATUS', 380, $y, 8, true, $grey);
        $pdf->text('PASSED', 380, $y + 20, 17, true, '1B8A3F');

        $y += 44;
        $pdf->rule($left, $y, 483);

        // Detail rows
        $rows = [
            ['Product', $report->product_label],
            ['Batch number', $report->batch_number],
            ['Date tested', $report->tested_on?->format('F j, Y') ?? '—'],
            ['Testing laboratory', $report->lab_name ?: 'Independent accredited laboratory'],
            ['Method', 'RP-HPLC / UV 214nm, confirmed by LC-MS'],
            ['Appearance', 'White lyophilised powder'],
            ['Solubility', 'Clear, colourless solution on reconstitution'],
            ['Purity result', $report->purity === 'N/A' ? 'Not applicable' : $report->purity],
            ['Specification', $report->purity === 'N/A' ? 'Sterility / endotoxin panel' : 'Not less than 99.0%'],
            ['Storage', 'Lyophilised: -20C. Reconstituted: 2-8C, use within 30 days.'],
        ];

        $y += 26;

        foreach ($rows as [$label, $value]) {
            $pdf->text(Str::upper($label), $left, $y, 8, true, $grey);
            $pdf->text((string) $value, 210, $y, 11);
            $y += 27;
        }

        $pdf->rule($left, $y, 483);
        $y += 26;

        $pdf->text('RESULT', $left, $y, 8, true, $grey);
        $pdf->text(
            $report->purity === 'N/A'
                ? 'Conforms to specification for sterility and endotoxin limits.'
                : "Identity confirmed. Purity {$report->purity} meets the release specification.",
            210,
            $y,
            11
        );

        // Disclaimer, boxed
        $y += 42;
        $pdf->rect($left, $y, 483, 74, 'F5F2E8');
        $pdf->text('IMPORTANT', $left + 14, $y + 20, 8, true, $gold);

        foreach ([
            'This material is supplied strictly for in-vitro laboratory research. It is not a drug,',
            'food or cosmetic, has not been evaluated by the FDA, and is not for human or',
            'veterinary consumption. Retain this certificate for your records.',
        ] as $i => $line) {
            $pdf->text($line, $left + 14, $y + 38 + ($i * 13), 9, false, '444444');
        }

        // Footer
        $pdf->rule($left, 760, 483);
        $pdf->text('Powered Up Peptides  |  poweredup.test  |  Batch '.$report->batch_number, $left, 778, 8, false, $grey);
        $pdf->text('SAMPLE DOCUMENT - PLACEHOLDER DATA', $left, 793, 8, true, 'B00020');

        return $pdf->render();
    }
}
