<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Pages;

use App\Models\Egg;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Smayt\UserGameServerCreator\Models\UgscEggImage;
use Smayt\UserGameServerCreator\Services\UgscImageService;

class ManageIconSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'ugsc-icon-settings';
    protected static ?string $title = 'Icon Settings';
    protected string $view = 'ugsc::icon-settings';

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'tabler-photo-cog';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Game Server Creator Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    public function schema(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Egg::query())
            ->searchable(true)
            ->defaultPaginationPageOption(25)
            ->columns([
                ImageColumn::make('grid_image')
                    ->label('Grid')
                    ->alignCenter()
                    ->getStateUsing(fn (Egg $record) => UgscEggImage::forEgg($record->id)?->getGridUrl()),
                ImageColumn::make('banner_image')
                    ->label('Banner')
                    ->alignCenter()
                    ->getStateUsing(fn (Egg $record) => UgscEggImage::forEgg($record->id)?->getBannerUrl()),
                ImageColumn::make('list_image')
                    ->label('List')
                    ->alignCenter()
                    ->getStateUsing(fn (Egg $record) => UgscEggImage::forEgg($record->id)?->getListUrl()),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('steam_app_id')
                    ->label('Steam App ID')
                    ->getStateUsing(fn (Egg $record) => UgscEggImage::forEgg($record->id)?->steam_app_id ?? '—'),
                TextColumn::make('images_status')
                    ->label('Images')
                    ->badge()
                    ->getStateUsing(function (Egg $record) {
                        $img = UgscEggImage::forEgg($record->id);
                        if (!$img) return 'None';
                        $count = (int) (bool) $img->grid_path + (int) (bool) $img->banner_path + (int) (bool) $img->list_path;
                        return "{$count}/3";
                    })
                    ->color(fn (string $state) => match($state) {
                        '3/3' => 'success',
                        'None' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('protection')
                    ->label('Protected')
                    ->getStateUsing(function (Egg $record) {
                        $img = UgscEggImage::forEgg($record->id);
                        if (!$img) return '—';
                        $parts = [];
                        if ($img->grid_protected) $parts[] = 'G';
                        if ($img->banner_protected) $parts[] = 'B';
                        if ($img->list_protected) $parts[] = 'L';
                        return empty($parts) ? '—' : implode(' ', $parts);
                    }),
            ])
            ->recordActions([
                Action::make('fetch_all')
                    ->label('Fetch All')
                    ->icon('tabler-brand-steam')
                    ->color('success')
                    ->form([
                        TextInput::make('steam_app_id')
                            ->label('Steam App ID')
                            ->numeric()
                            ->required()
                            ->placeholder('e.g. 892970 for Valheim')
                            ->default(fn (Egg $record) => UgscEggImage::forEgg($record->id)?->steam_app_id),
                    ])
                    ->action(function (Egg $record, array $data) {
                        $service = app(UgscImageService::class);
                        $appId = (int) $data['steam_app_id'];
                        $results = [
                            'grid'   => $service->fetchGrid($record, $appId, true),
                            'banner' => $service->fetchBanner($record, $appId, true),
                            'list'   => $service->fetchList($record, $appId, true),
                        ];
                        $fetched = count(array_filter($results));
                        Notification::make()
                            ->title("Fetched {$fetched}/3 images")
                            ->color($fetched === 3 ? 'success' : 'warning')
                            ->send();
                    }),

                Action::make('upload_grid')
                    ->label('Upload Grid')
                    ->icon('tabler-layout-grid')
                    ->color('gray')
                    ->form([
                        FileUpload::make('image')
                            ->label('Grid Image (portrait)')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']),
                        TextInput::make('steam_app_id')
                            ->label('Or fetch from Steam App ID')
                            ->numeric()
                            ->default(fn (Egg $record) => UgscEggImage::forEgg($record->id)?->steam_app_id),
                    ])
                    ->action(function (Egg $record, array $data) {
                        $service = app(UgscImageService::class);
                        if (!empty($data['image'])) {
                            $file = $data['image'];
                            $service->uploadImage($record, $file, 'grid')
                                ? Notification::make()->title('Grid image uploaded & protected')->success()->send()
                                : Notification::make()->title('Upload failed')->danger()->send();
                        } elseif (!empty($data['steam_app_id'])) {
                            $service->fetchGrid($record, (int) $data['steam_app_id'], true)
                                ? Notification::make()->title('Grid image fetched')->success()->send()
                                : Notification::make()->title('Failed to fetch grid image')->danger()->send();
                        }
                    }),

                Action::make('upload_banner')
                    ->label('Upload Banner')
                    ->icon('tabler-photo')
                    ->color('gray')
                    ->form([
                        FileUpload::make('image')
                            ->label('Banner Image (wide)')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']),
                        TextInput::make('steam_app_id')
                            ->label('Or fetch from Steam App ID')
                            ->numeric()
                            ->default(fn (Egg $record) => UgscEggImage::forEgg($record->id)?->steam_app_id),
                    ])
                    ->action(function (Egg $record, array $data) {
                        $service = app(UgscImageService::class);
                        if (!empty($data['image'])) {
                            $file = $data['image'];
                            $service->uploadImage($record, $file, 'banner')
                                ? Notification::make()->title('Banner image uploaded & protected')->success()->send()
                                : Notification::make()->title('Upload failed')->danger()->send();
                        } elseif (!empty($data['steam_app_id'])) {
                            $service->fetchBanner($record, (int) $data['steam_app_id'], true)
                                ? Notification::make()->title('Banner image fetched')->success()->send()
                                : Notification::make()->title('Failed to fetch banner image')->danger()->send();
                        }
                    }),

                Action::make('upload_list')
                    ->label('Upload List')
                    ->icon('tabler-list')
                    ->color('gray')
                    ->form([
                        FileUpload::make('image')
                            ->label('List Image (small)')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']),
                        TextInput::make('steam_app_id')
                            ->label('Or fetch from Steam App ID')
                            ->numeric()
                            ->default(fn (Egg $record) => UgscEggImage::forEgg($record->id)?->steam_app_id),
                    ])
                    ->action(function (Egg $record, array $data) {
                        $service = app(UgscImageService::class);
                        if (!empty($data['image'])) {
                            $file = $data['image'];
                            $service->uploadImage($record, $file, 'list')
                                ? Notification::make()->title('List image uploaded & protected')->success()->send()
                                : Notification::make()->title('Upload failed')->danger()->send();
                        } elseif (!empty($data['steam_app_id'])) {
                            $service->fetchList($record, (int) $data['steam_app_id'], true)
                                ? Notification::make()->title('List image fetched')->success()->send()
                                : Notification::make()->title('Failed to fetch list image')->danger()->send();
                        }
                    }),

                Action::make('toggle_protection')
                    ->label('Protection')
                    ->icon('tabler-lock')
                    ->color('warning')
                    ->form(function (Egg $record) {
                        $img = UgscEggImage::forEgg($record->id);
                        return [
                            \Filament\Forms\Components\Toggle::make('grid_protected')
                                ->label('Protect Grid')
                                ->default($img?->grid_protected ?? false),
                            \Filament\Forms\Components\Toggle::make('banner_protected')
                                ->label('Protect Banner')
                                ->default($img?->banner_protected ?? false),
                            \Filament\Forms\Components\Toggle::make('list_protected')
                                ->label('Protect List')
                                ->default($img?->list_protected ?? false),
                        ];
                    })
                    ->action(function (Egg $record, array $data) {
                        $img = UgscEggImage::forEggOrNew($record->id);
                        $img->grid_protected   = $data['grid_protected'];
                        $img->banner_protected = $data['banner_protected'];
                        $img->list_protected   = $data['list_protected'];
                        $img->save();
                        Notification::make()->title('Protection updated')->success()->send();
                    }),

                Action::make('clear_all')
                    ->label('Clear')
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Egg $record) {
                        app(UgscImageService::class)->clearAll($record);
                        Notification::make()->title('Images cleared')->success()->send();
                    }),
            ])
            ->toolbarActions([
                Action::make('bulk_clear_all')
                    ->label('Clear All Images')
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will delete ALL ugsc images for ALL eggs. This cannot be undone.')
                    ->action(function () {
                        $service = app(UgscImageService::class);
                        $eggs = Egg::all();
                        foreach ($eggs as $egg) {
                            $service->clearAll($egg);
                        }
                        Notification::make()->title('All images cleared')->success()->send();
                        $this->redirect(static::getUrl());
                    }),

                Action::make('bulk_fetch_all')
                    ->label('Bulk Fetch All Missing')
                    ->icon('tabler-brand-steam')
                    ->requiresConfirmation()
                    ->modalDescription('Searches Steam by egg name and fetches all 3 image types for unprotected eggs missing images.')
                    ->action(function () {
                        $service = app(UgscImageService::class);
                        $eggs = Egg::all();
                        $fetched = 0; $skipped = 0; $failed = 0;
                        foreach ($eggs as $egg) {
                            $existing = UgscEggImage::forEgg($egg->id);
                            $allDone = $existing
                                && ($existing->grid_path || $existing->grid_protected)
                                && ($existing->banner_path || $existing->banner_protected)
                                && ($existing->list_path || $existing->list_protected);
                            if ($allDone) { $skipped++; continue; }
                            $appId = $existing?->steam_app_id ?? $service->searchSteamAppId($egg->name);
                            if (!$appId) { $failed++; continue; }
                            $results = $service->fetchAll($egg, $appId);
                            count(array_filter($results)) > 0 ? $fetched++ : $failed++;
                            usleep(300000);
                        }
                        Notification::make()
                            ->title("Bulk fetch: {$fetched} fetched, {$skipped} skipped, {$failed} failed")
                            ->success()
                            ->send();
                        $this->redirect(static::getUrl());
                    }),
            ]);
    }
}
