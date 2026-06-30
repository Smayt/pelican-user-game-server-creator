<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Resources\Categories;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Smayt\UserGameServerCreator\Filament\Admin\Resources\Categories\Pages\ManageCategories;
use Smayt\UserGameServerCreator\Models\Category;

class CategoriesResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static string|BackedEnum|null $navigationIcon = 'tabler-category';
    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return 'Game Server Creator Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Server Categories';
    }

    public static function getModelLabel(): string
    {
        return 'Category';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Categories';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->hidden()
                ->dehydrated(),
            Grid::make(5)
                ->columnSpanFull()
                ->schema([
                    Group::make([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Select::make('icon')
                            ->label('Icon (or type exact name)')
                            ->options(fn () => collect(require base_path('plugins/user-game-server-creator/src/Support/TablerIconList.php'))
                                ->take(200)
                                ->toArray())
                            ->getSearchResultsUsing(function (string $search) {
                                $all = require base_path('plugins/user-game-server-creator/src/Support/TablerIconList.php');
                                $search = str($search)->lower()->replace(' ', '-');
                                return collect($all)
                                    ->filter(fn ($label, $key) => str_contains($key, (string) $search))
                                    ->take(50)
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value) => $value)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->native(false)
                            ->hint('Search Tabler icons by name'),
                        ViewField::make('icon_preview')
                            ->view('ugsc::components.icon-preview')
                            ->label('')
                            ->statePath('icon')
                            ->dehydrated(false),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ])->columnSpan(2),
                    ViewField::make('icon')
                        ->view('ugsc::components.icon-picker')
                        ->label('Browse icons')
                        ->live()
                        ->dehydrated(false)
                        ->columnSpan(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->hidden(),
                IconColumn::make('icon')
                    ->label('Icon')
                    ->icon(fn ($state) => $state ?: null)
                    ->size('lg'),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('egg_settings_count')
                    ->label('Eggs')
                    ->counts('eggSettings'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
