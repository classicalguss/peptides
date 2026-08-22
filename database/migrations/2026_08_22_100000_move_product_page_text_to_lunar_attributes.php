<?php

use App\FieldTypes\Textarea;
use App\FieldTypes\TextList;
use App\Models\Product;
use App\Support\WebsitePageAttributes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Lunar\FieldTypes\Dropdown;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\Models\ProductType;

/**
 * Product page copy used to live in an app-specific product_profiles table
 * beside Lunar's products. It now lives where Lunar keeps every other product
 * field: as attributes in the "Website Page" group, visible on the standard
 * product edit screen and scoped by product type. The compound-vs-collection
 * distinction is the product type alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([Product::TYPE_COMPOUND, Product::TYPE_COLLECTION] as $name) {
            ProductType::query()->firstOrCreate(['name' => $name]);
        }

        WebsitePageAttributes::ensure();

        if (! Schema::hasTable('product_profiles')) {
            return;
        }

        $profiles = DB::table('product_profiles')->orderBy('id')->get();

        if ($profiles->isNotEmpty()) {
            File::ensureDirectoryExists(storage_path('app/backups'));
            File::put(
                storage_path('app/backups/product_profiles-'.now()->format('Ymd-His').'.json'),
                $profiles->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            );
        }

        $typeIds = [
            'compound' => ProductType::query()->where('name', Product::TYPE_COMPOUND)->value('id'),
            'stack' => ProductType::query()->where('name', Product::TYPE_COLLECTION)->value('id'),
        ];

        foreach ($profiles as $profile) {
            $product = Product::query()->withTrashed()->find($profile->product_id);

            if (! $product) {
                continue;
            }

            $fields = [
                'subtitle' => $this->text($profile->subtitle),
                'tagline' => $this->text($profile->tagline),
                'protocol_label' => $this->text($profile->protocol_label),
                'dose' => $this->text($profile->dose),
                'summary' => $this->textarea($profile->summary),
                'overview' => $this->textarea($profile->overview),
                'research_info' => $this->textarea($profile->research_info),
                'storage' => $this->textarea($profile->storage),
                'highlights' => $this->list($profile->highlights),
                'pillars' => $this->list($profile->pillars),
                'accent' => new Dropdown($profile->accent ?: 'gold'),
                'display_order' => new Number((int) $profile->position),
            ];

            $data = collect($product->attribute_data ?? []);

            foreach ($fields as $handle => $field) {
                if ($field !== null) {
                    $data->put($handle, $field);
                }
            }

            $product->attribute_data = $data;
            $product->product_type_id = $typeIds[$profile->kind] ?? $product->product_type_id;

            Product::withoutSyncingToSearch(fn () => $product->save());
        }

        Schema::drop('product_profiles');
    }

    /**
     * Recreates the empty table only; the data lives in the attributes and in
     * the JSON backup written by up().
     */
    public function down(): void
    {
        Schema::create('product_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('lunar_products')->cascadeOnDelete();
            $table->string('kind')->default('compound');
            $table->string('handle')->unique();
            $table->string('accent')->default('gold');
            $table->string('subtitle')->nullable();
            $table->string('tagline')->nullable();
            $table->string('protocol_label')->nullable();
            $table->string('dose')->nullable();
            $table->text('summary')->nullable();
            $table->text('overview')->nullable();
            $table->text('research_info')->nullable();
            $table->text('dosage')->nullable();
            $table->text('storage')->nullable();
            $table->json('benefits')->nullable();
            $table->json('highlights')->nullable();
            $table->json('pillars')->nullable();
            $table->json('audience')->nullable();
            $table->json('faq')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['kind', 'position']);
        });
    }

    private function text(?string $value): ?Text
    {
        return filled($value) ? new Text($value) : null;
    }

    private function textarea(?string $value): ?Textarea
    {
        return filled($value) ? new Textarea($value) : null;
    }

    private function list(?string $json): ?TextList
    {
        $items = is_string($json) ? json_decode($json, true) : null;

        return is_array($items) && $items !== [] ? new TextList($items) : null;
    }
};
