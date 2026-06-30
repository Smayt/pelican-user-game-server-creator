<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Resources\UserResourceLimits;

use App\Models\User;
use BackedEnum;
use App\Enums\SubuserPermission;
use App\Models\Server;
use App\Models\Subuser;
use App\Services\Subusers\SubuserCreationService;
use App\Services\Subusers\SubuserDeletionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Smayt\UserGameServerCreator\Models\ServerDeleteGrant;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Smayt\UserGameServerCreator\Filament\Admin\Resources\UserResourceLimits\Pages\ManageUserResourceLimits;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;

class UserResourceLimitsResource extends Resource
{
    protected static ?string $model = UserResourceLimits::class;
    protected static string|BackedEnum|null $navigationIcon = 'tabler-cpu';
    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'Game Server Creator Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'User Resource Limits';
    }

    public static function getModelLabel(): string
    {
        return 'User Resource Limit';
    }

    public static function getPluralModelLabel(): string
    {
        return 'User Resource Limits';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('User')
                ->options(fn () => User::all()->mapWithKeys(fn ($u) => [$u->id => $u->username]))
                ->searchable()
                ->required(),
            TextInput::make('cpu')
                ->label('CPU (%)')
                ->hint('0 = unlimited')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            TextInput::make('memory')
                ->label('Memory (MiB)')
                ->hint('0 = unlimited')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            TextInput::make('disk')
                ->label('Disk Space (MiB)')
                ->hint('0 = unlimited')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            TextInput::make('server_limit')
                ->label('Server Limit')
                ->hint('0 or empty = unlimited')
                ->numeric()
                ->minValue(0)
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cpu')
                    ->label('CPU')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->cpu > 0 ? $record->cpu . '%' : 'Unlimited')
                    ->color(fn ($record) => $record->cpu > 0 ? 'warning' : 'gray'),
                TextColumn::make('memory')
                    ->label('Memory')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->memory > 0 ? $record->memory . ' MiB' : 'Unlimited')
                    ->color(fn ($record) => $record->memory > 0 ? 'warning' : 'gray'),
                TextColumn::make('disk')
                    ->label('Disk')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->disk > 0 ? $record->disk . ' MiB' : 'Unlimited')
                    ->color(fn ($record) => $record->disk > 0 ? 'warning' : 'gray'),
                TextColumn::make('server_limit')
                    ->label('Server Limit')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->server_limit > 0 ? $record->server_limit : 'Unlimited')
                    ->color(fn ($record) => $record->server_limit > 0 ? 'warning' : 'gray'),
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
            'index' => ManageUserResourceLimits::route('/'),
        ];
    }
}
