<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Resources\EggSettings;

use App\Models\Egg;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Smayt\UserGameServerCreator\Filament\Admin\Resources\EggSettings\Pages\ManageEggSettings;
use Smayt\UserGameServerCreator\Models\Category;
use Smayt\UserGameServerCreator\Models\EggSettings;

class EggSettingsResource extends Resource
{
    protected static ?string $model = EggSettings::class;
    protected static string|BackedEnum|null $navigationIcon = 'tabler-egg';
    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return 'Game Server Creator Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Egg Settings';
    }

    public static function getModelLabel(): string
    {
        return 'Egg Setting';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Egg Settings';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Egg')
                ->schema([
                    Select::make('egg_id')
                        ->label('Egg')
                        ->options(fn () => Egg::all()->mapWithKeys(fn ($e) => [$e->id => $e->name]))
                        ->searchable()
                        ->required()
                        ->unique(ignoreRecord: true),
                    Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => Category::orderBy('sort_order')->pluck('name', 'id'))
                        ->nullable()
                        ->searchable(),
                ]),
            Section::make('Visibility')
                ->columns(3)
                ->schema([
                    Toggle::make('hidden')
                        ->label('Hidden from users')
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('If enabled, users cannot create servers with this egg.')
                        ->inline(false),
                    Toggle::make('popular')
                        ->label('Mark as Popular')
                        ->inline(false),
                    Toggle::make('slots_mode')
                        ->label('Slots mode (voice servers)')
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('Use slots instead of player count. RAM scales per slot.')
                        ->inline(false),
                ]),
            Section::make('Resource Requirements')
                ->description('Resources auto-calculated based on player count. Users can still adjust manually.')
                ->columns(2)
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('min_players')
                            ->label('Min players/slots')
                            ->numeric()->minValue(1)->default(2)->required(),
                        TextInput::make('max_players')
                            ->label('Max players/slots')
                            ->numeric()->minValue(1)->default(10)->required(),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('ram_base')
                            ->label('RAM at min players (MiB)')
                            ->numeric()->minValue(0)->default(1024)->required(),
                        TextInput::make('ram_max')
                            ->label('RAM at max players (MiB)')
                            ->numeric()->minValue(0)->default(4096)->required(),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('cpu_base')
                            ->label('CPU at min players (%)')
                            ->numeric()->minValue(0)->default(100)->required(),
                        TextInput::make('cpu_max')
                            ->label('CPU at max players (%)')
                            ->numeric()->minValue(0)->default(200)->required(),
                    ]),
                    TextInput::make('disk')
                        ->label('Disk Space (MiB)')
                        ->numeric()->minValue(0)->default(10240)->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $defaultIcon = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents(public_path('pelican.svg')));

        return $table
            ->columns([
                ImageColumn::make('egg.icon')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn (EggSettings $record) => $record->egg?->icon ?: $defaultIcon),
                TextColumn::make('egg.name')
                    ->label('Egg')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->default('—'),
                IconColumn::make('popular')
                    ->label('Popular')
                    ->boolean(),
                IconColumn::make('hidden')
                    ->label('Hidden')
                    ->boolean(),
                IconColumn::make('slots_mode')
                    ->label('Slots')
                    ->boolean(),
                TextColumn::make('ram_base')
                    ->label('RAM min')
                    ->getStateUsing(fn ($record) => $record->ram_base . ' MiB'),
                TextColumn::make('ram_max')
                    ->label('RAM max')
                    ->getStateUsing(fn ($record) => $record->ram_max . ' MiB'),
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
            'index' => ManageEggSettings::route('/'),
        ];
    }
}
