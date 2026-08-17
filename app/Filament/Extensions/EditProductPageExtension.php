<?php

namespace App\Filament\Extensions;

use App\Models\ProductProfile;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lunar\Admin\Support\Extending\EditPageExtension;

class EditProductPageExtension extends EditPageExtension
{
    /** @var array<string, mixed>|null */
    private ?array $pendingPageText = null;

    public function headerActions(array $actions): array
    {
        $profile = $this->profile();

        if (! $profile) {
            return $actions;
        }

        return [
            Actions\Action::make('preview_storefront')
                ->label('Preview Storefront Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(route($profile->isStack() ? 'stack' : 'compound', $profile->handle))
                ->openUrlInNewTab(),
            ...$actions,
        ];
    }

    public function beforeFill(array $data): array
    {
        $profile = $this->profile();

        if (! $profile) {
            return $data;
        }

        $pageText = $profile->only($this->editableColumns());

        if ($profile->isStack()) {
            $pageText['stack_benefits'] = $profile->benefits ?? [];
        }

        $data['page_text'] = $pageText;

        return $data;
    }

    public function beforeUpdate(array $data, Model $record): array
    {
        $pageText = Arr::pull($data, 'page_text');

        if (! is_array($pageText)) {
            return $data;
        }

        $profile = ProductProfile::query()->where('product_id', $record->getKey())->first();

        if (! $profile) {
            return $data;
        }

        if ($profile->isStack()) {
            $pageText['benefits'] = $pageText['stack_benefits'] ?? [];
        }

        unset($pageText['stack_benefits']);

        $this->pendingPageText = Arr::only($pageText, $this->editableColumns());

        return $data;
    }

    public function afterUpdate(Model $record, array $data): Model
    {
        if ($this->pendingPageText !== null) {
            $profile = ProductProfile::query()->where('product_id', $record->getKey())->first();

            if ($profile) {
                $profile->fill($this->pendingPageText);

                if ($profile->isDirty()) {
                    $profile->save();
                }
            }

            $this->pendingPageText = null;
        }

        return $record;
    }

    /**
     * @return array<int, string>
     */
    private function editableColumns(): array
    {
        return [
            'subtitle',
            'protocol_label',
            'tagline',
            'summary',
            'overview',
            'research_info',
            'storage',
            'benefits',
            'highlights',
            'pillars',
            'audience',
            'faq',
        ];
    }

    private function profile(): ?ProductProfile
    {
        $record = $this->caller?->getRecord();

        return $record
            ? ProductProfile::query()->where('product_id', $record->getKey())->first()
            : null;
    }
}
