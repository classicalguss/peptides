<?php

namespace Tests\Feature;

use App\Filament\Resources\CoaReportResource\Pages\CreateCoaReport;
use App\Models\CoaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Lunar\Admin\Models\Staff;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Tests\TestCase;

class AdminCoaReportsTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsAdmin(): void
    {
        $this->actingAs(Staff::factory()->create(['admin' => true]), 'staff');
    }

    private function makeProduct(string $name): Product
    {
        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);

        return Product::factory()->create([
            'product_type_id' => ProductType::factory()->create(['name' => 'Research Compound'])->id,
            'attribute_data' => ['name' => new TranslatedText(collect(['en' => new Text($name)]))],
        ]);
    }

    public function test_the_lab_reports_admin_screens_load(): void
    {
        $this->signInAsAdmin();
        $product = $this->makeProduct('BPC-157 20mg');
        $report = CoaReport::create(['product_id' => $product->id, 'product_label' => 'BPC-157 20mg', 'status' => CoaReport::STATUS_UNPUBLISHED, 'status_label' => 'Additional Testing in Progress', 'status_color' => '#fbbf24']);

        $this->get('/lunar/coa-reports')->assertOk()->assertSee('BPC-157 20mg')->assertSee('Add product batch');
        $this->get('/lunar/coa-reports/create')->assertOk();
        $this->get("/lunar/coa-reports/{$report->id}/edit")->assertOk()->assertSee('Publication status')->assertSee('Additional Testing in Progress');
    }

    public function test_an_admin_can_add_a_batch_record_for_a_new_product(): void
    {
        $this->signInAsAdmin();
        Storage::fake('public');
        $product = $this->makeProduct('Semaglutide 5mg');

        Livewire::test(CreateCoaReport::class)
            ->fillForm([
                'product_id' => $product->id,
                'product_label' => 'Semaglutide 5mg',
                'status' => CoaReport::STATUS_PASS,
                'batch_number' => 'PUP-SG5-001',
                'tested_on' => '2026-09-01',
                'purity' => '99.12%',
                'lab_name' => 'ILS Laboratories',
                'pdf_path' => UploadedFile::fake()->create('PUP-SG5-001.pdf', 20, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $report = CoaReport::query()->where('product_id', $product->id)->firstOrFail();

        $this->assertSame('PUP-SG5-001', $report->batch_number);
        $this->assertSame('pass', $report->status);
        $this->assertStringStartsWith('coa/', (string) $report->pdf_path);
        Storage::disk('public')->assertExists($report->pdf_path);
    }

    public function test_a_passing_batch_requires_its_details(): void
    {
        $this->signInAsAdmin();
        $product = $this->makeProduct('Semaglutide 5mg');

        Livewire::test(CreateCoaReport::class)
            ->fillForm(['product_id' => $product->id, 'product_label' => 'Semaglutide 5mg', 'status' => CoaReport::STATUS_PASS])
            ->call('create')
            ->assertHasFormErrors(['batch_number', 'tested_on', 'purity', 'lab_name', 'pdf_path']);
    }
}
