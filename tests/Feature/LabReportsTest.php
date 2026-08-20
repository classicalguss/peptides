<?php

namespace Tests\Feature;

use App\Models\CoaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_passing_batch_shows_its_full_certificate_details(): void
    {
        CoaReport::create([
            'product_label' => 'BPC-157 20mg',
            'batch_number' => 'PUP-BC20-001',
            'tested_on' => '2026-08-13',
            'purity' => '99.91%',
            'lab_name' => 'ILS Laboratories',
            'pdf_path' => 'coa/PUP-BC20-001.pdf',
            'status' => CoaReport::STATUS_PASS,
        ]);

        $this->get('/lab-reports')
            ->assertOk()
            ->assertSee('PUP-BC20-001')
            ->assertSee('99.91%')
            ->assertSee('ILS Laboratories')
            ->assertSee('Aug 13, 2026')
            ->assertSee('Pass')
            ->assertSee('View COA')
            ->assertSee('/storage/coa/PUP-BC20-001.pdf');
    }

    public function test_a_batch_under_additional_testing_shows_only_the_status_message(): void
    {
        CoaReport::create([
            'product_label' => 'NAD+ 1000mg',
            'status' => CoaReport::STATUS_TESTING,
        ]);

        $this->get('/lab-reports')
            ->assertOk()
            ->assertSee('NAD+ 1000mg')
            ->assertSee('Additional Testing in Progress')
            ->assertSee('Updated analytical documentation will be published upon completion of testing.')
            ->assertDontSee('View COA');
    }

    public function test_a_batch_awaiting_documentation_shows_no_fabricated_details(): void
    {
        CoaReport::create([
            'product_label' => 'BAC Water 10ml',
            'status' => CoaReport::STATUS_PENDING,
        ]);

        $this->get('/lab-reports')
            ->assertOk()
            ->assertSee('BAC Water 10ml')
            ->assertSee('Documentation Pending')
            ->assertDontSee('View COA')
            ->assertDontSee('Pending Upload');
    }

    public function test_batch_search_matches_the_new_batch_numbers(): void
    {
        CoaReport::create([
            'product_label' => 'BPC-157 20mg',
            'batch_number' => 'PUP-BC20-001',
            'tested_on' => '2026-08-13',
            'purity' => '99.91%',
            'lab_name' => 'ILS Laboratories',
            'pdf_path' => 'coa/PUP-BC20-001.pdf',
            'status' => CoaReport::STATUS_PASS,
        ]);
        CoaReport::create([
            'product_label' => 'TB-500 20mg',
            'batch_number' => 'PUP-BT20-001',
            'tested_on' => '2026-08-13',
            'purity' => '99.70%',
            'lab_name' => 'ILS Laboratories',
            'pdf_path' => 'coa/PUP-BT20-001.pdf',
            'status' => CoaReport::STATUS_PASS,
        ]);

        $this->get('/lab-reports?batch=PUP-BC20')
            ->assertOk()
            ->assertSee('PUP-BC20-001')
            ->assertDontSee('PUP-BT20-001');
    }
}
