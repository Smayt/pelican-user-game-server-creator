<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Pages;

use App\Enums\SubuserPermission;
use App\Models\Server;
use App\Models\Subuser;
use App\Services\Subusers\SubuserCreationService;
use App\Services\Subusers\SubuserDeletionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Smayt\UserGameServerCreator\Models\ServerDeleteGrant;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;

class EditPermissions extends Page
{
    protected static ?string $slug = 'ugsc-permissions/edit';
    protected static ?string $title = 'Edit Server Access';
    protected string $view = 'ugsc::edit-permissions';
    protected static bool $shouldRegisterNavigation = false;

    public ?int $limitId = null;

    public function mount(): void
    {
        $this->limitId = (int) request()->query('id');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'tabler-shield-lock';
    }

    public function schema(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function getViewData(): array
    {
        $limitId = $this->limitId;
        $limit = UserResourceLimits::findOrFail($limitId);
        $userId = $limit->user_id;

        $visibleServerIds = Subuser::where('user_id', $userId)->pluck('server_id')->toArray();
        $deletableServerIds = ServerDeleteGrant::where('user_id', $userId)->pluck('server_id')->toArray();
        $servers = Server::whereNot('owner_id', $userId)->orderBy('name')->get(['id', 'name']);

        return [
            'limit'              => $limit,
            'servers'            => $servers,
            'visibleServerIds'   => $visibleServerIds,
            'deletableServerIds' => $deletableServerIds,
        ];
    }

    public function getHeaderActions(): array
    {
        $limitId = $this->limitId;

        return [
            Action::make('grant_all_visibility')
                ->label('Grant All Visibility')
                ->icon('tabler-eye')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () use ($limitId) {
                    $limit = UserResourceLimits::findOrFail($limitId);
                    $userId = $limit->user_id;
                    $user = $limit->user;
                    $servers = Server::whereNot('owner_id', $userId)->get();
                    $creationService = app(SubuserCreationService::class);
                    $count = 0;
                    foreach ($servers as $server) {
                        try {
                            $creationService->handle($server, $user->email, [
                                SubuserPermission::ControlConsole->value,
                                SubuserPermission::ControlStart->value,
                                SubuserPermission::ControlStop->value,
                                SubuserPermission::ControlRestart->value,
                                SubuserPermission::WebsocketConnect->value,
                            ]);
                            $count++;
                        } catch (\Throwable $e) {
                            \Log::warning('UGSC: failed to grant visibility', ['server_id' => $server->id, 'user' => $user->email, 'error' => $e->getMessage()]);
                        }
                    }
                    Notification::make()->title("Granted visibility on {$count} servers")->success()->send();
                    $this->redirect(static::getUrl(['id' => $this->limitId]));
                }),
            Action::make('grant_all_delete')
                ->label('Grant All Delete')
                ->icon('tabler-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Grants delete rights on every server this user can currently see.')
                ->action(function () use ($limitId) {
                    $limit = UserResourceLimits::findOrFail($limitId);
                    $count = ServerDeleteGrant::grantAllVisibleToUser($limit->user_id);
                    if ($count === 0) {
                        Notification::make()
                            ->title('No delete rights granted')
                            ->body('This user has no visibility grants yet. Grant visibility first, then delete rights.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()->title("Granted delete rights on {$count} servers")->success()->send();
                    }
                    $this->redirect(static::getUrl(['id' => $this->limitId]));
                }),
            Action::make('revoke_all_visibility')
                ->label('Revoke All Visibility')
                ->icon('tabler-eye-off')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Removes visibility access on every server granted to this user (except their own servers).')
                ->action(function () use ($limitId) {
                    $limit = UserResourceLimits::findOrFail($limitId);
                    $userId = $limit->user_id;
                    $deletionService = app(SubuserDeletionService::class);
                    $subusers = Subuser::where('user_id', $userId)->get();
                    $count = 0;
                    foreach ($subusers as $subuser) {
                        $server = Server::find($subuser->server_id);
                        if ($server) {
                            $deletionService->handle($subuser, $server);
                            $count++;
                        }
                    }
                    $deleteGrantsCleared = ServerDeleteGrant::revokeAllFromUser($userId);
                    $message = "Revoked visibility on {$count} servers";
                    if ($deleteGrantsCleared > 0) {
                        $message .= " (also cleared {$deleteGrantsCleared} orphaned delete grants)";
                    }
                    Notification::make()->title($message)->success()->send();
                    $this->redirect(static::getUrl(['id' => $this->limitId]));
                }),
            Action::make('revoke_all_delete')
                ->label('Revoke All Delete')
                ->icon('tabler-trash-off')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Removes delete rights on every server granted to this user.')
                ->action(function () use ($limitId) {
                    $limit = UserResourceLimits::findOrFail($limitId);
                    $count = ServerDeleteGrant::revokeAllFromUser($limit->user_id);
                    Notification::make()->title("Revoked delete rights on {$count} servers")->success()->send();
                    $this->redirect(static::getUrl(['id' => $this->limitId]));
                }),
        ];
    }
}
