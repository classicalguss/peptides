<?php

namespace App\Filament\Support;

use App\Filament\Clusters\WebsiteLists;
use App\Models\WebsiteListItem;
use App\Support\WebsiteList;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One dedicated admin page per repeating-content list: a repeater of the
 * list's items that can be added to, removed from and dragged into order,
 * then saved in one go. Subclasses only name the list they manage.
 */
abstract class WebsiteListPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = WebsiteLists::class;

    protected static string $view = 'filament.pages.website-list';

    protected static string $listKey;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function listKey(): string
    {
        return static::$listKey;
    }

    /** @return array<string, mixed> */
    public static function definition(): array
    {
        return WebsiteList::definitions()[static::$listKey] ?? [];
    }

    public static function getNavigationLabel(): string
    {
        return static::definition()['label'] ?? Str::headline(static::$listKey);
    }

    public static function getNavigationGroup(): ?string
    {
        return static::definition()['page'] ?? null;
    }

    /**
     * Keep the sub-navigation in the order lists are declared in config,
     * which follows the order of pages on the site.
     */
    public static function getNavigationSort(): ?int
    {
        return array_search(static::$listKey, array_keys(WebsiteList::definitions()), true) ?: 0;
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function getSubheading(): ?string
    {
        $definition = static::definition();

        return trim(($definition['page'] ?? '').' · '.($definition['location_hint'] ?? ''), ' ·');
    }

    public function mount(): void
    {
        $items = WebsiteListItem::query()
            ->where('list_key', static::$listKey)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['heading', 'body', 'extra'])
            ->map(fn (WebsiteListItem $item) => $item->only(array_keys(static::definition()['fields'] ?? [])))
            ->all();

        $this->form->fill(['items' => $items]);
    }

    public function form(Form $form): Form
    {
        $fields = static::definition()['fields'] ?? [];
        $schema = [];

        if (isset($fields['extra'])) {
            $schema[] = Forms\Components\TextInput::make('extra')->label($fields['extra'])->required()->maxLength(255);
        }

        if (isset($fields['heading'])) {
            $schema[] = Forms\Components\TextInput::make('heading')->label($fields['heading'])->required()->maxLength(255);
        }

        if (isset($fields['body'])) {
            $schema[] = Forms\Components\Textarea::make('body')
                ->label($fields['body'])
                ->required()
                ->rows(isset($fields['heading']) ? 3 : 2)
                ->maxLength(5000)
                ->columnSpanFull();
        }

        return $form
            ->schema([
                Forms\Components\Repeater::make('items')
                    ->label('Items')
                    ->schema($schema)
                    ->columns(isset($fields['extra']) && isset($fields['heading']) ? 2 : 1)
                    ->addActionLabel('Add item')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['heading'] ?? Str::limit((string) ($state['body'] ?? ''), 60))
                    ->defaultItems(0)
                    ->minItems(0),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $items = $this->form->getState()['items'] ?? [];

        DB::transaction(function () use ($items): void {
            WebsiteListItem::query()->where('list_key', static::$listKey)->delete();

            foreach (array_values($items) as $index => $item) {
                WebsiteListItem::create([
                    'list_key' => static::$listKey,
                    'sort_order' => $index + 1,
                    'heading' => $item['heading'] ?? null,
                    'body' => $item['body'] ?? null,
                    'extra' => $item['extra'] ?? null,
                ]);
            }
        });

        Cache::forget('website-list.items');

        Notification::make()
            ->title('List saved')
            ->body(count($items).' item'.(count($items) === 1 ? '' : 's').' now shown on the website.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $route = static::definition()['route_name'] ?? null;

        return [
            Action::make('preview')
                ->label('Preview Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): ?string => $route ? route($route) : null)
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($route)),
        ];
    }
}
