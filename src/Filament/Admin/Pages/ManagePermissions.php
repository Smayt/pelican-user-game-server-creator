<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Pages;

use App\Models\Server;
use App\Models\Subuser;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Smayt\UserGameServerCreator\Models\ServerDeleteGrant;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;

class ManagePermissions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'ugsc-permissions';
    protected static ?string $title = 'Permissions';
    protected string $view = 'ugsc::permissions';

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'tabler-shield-lock';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Game Server Creator Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return 50;
    }

    public function schema(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(UserResourceLimits::query())
            ->searchable(false)
            ->columns([
                TextColumn::make('user.username')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('servers_owned')
                    ->label('Owns')
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(fn (UserResourceLimits $record) => Server::where('owner_id', $record->user_id)->count()),
                TextColumn::make('servers_visible')
                    ->label('Can See (granted)')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (UserResourceLimits $record) => Subuser::where('user_id', $record->user_id)->count()),
                TextColumn::make('servers_deletable')
                    ->label('Can Delete (granted)')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(fn (UserResourceLimits $record) => ServerDeleteGrant::where('user_id', $record->user_id)->count()),
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('Manage')
                    ->icon('tabler-adjustments')
                    ->color('primary')
                    ->url(fn (UserResourceLimits $record) => '/admin/ugsc-permissions/edit?id=' . $record->id),
            ]);
    }
}
