<?php
namespace Smayt\UserGameServerCreator\Filament\Components\Actions;

use App\Models\Server;
use App\Services\Servers\ServerDeletionService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Smayt\UserGameServerCreator\Models\ServerDeleteGrant;
use Smayt\UserGameServerCreator\Models\ServerDeleteLock;

class DeleteServerAction
{
    public static function make(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('ugsc_delete_server')
                ->label('Delete')
                ->icon('tabler-trash')
                ->color('danger')
                ->size(Size::ExtraLarge)
                ->requiresConfirmation()
                ->modalHeading('Delete Server')
                ->modalDescription('This will permanently delete this server and all its data. This cannot be undone.')
                ->modalSubmitActionLabel('Delete Server')
                ->visible(function () {
                    /** @var Server $server */
                    $server = Filament::getTenant();
                    $user = auth()->user();
                    if ($user->isRootAdmin()) {
                        return true;
                    }
                    $isOwner = $server->owner_id === $user->id;
                    if ($isOwner) {
                        return !ServerDeleteLock::isLocked($server->id);
                    }
                    return ServerDeleteGrant::isGranted($user->id, $server->id);
                })
                ->action(function () {
                    /** @var Server $server */
                    $server = Filament::getTenant();
                    app(ServerDeletionService::class)->handle($server);
                    Notification::make()
                        ->title('Server deleted')
                        ->success()
                        ->send();
                    redirect('/');
                }),
        ])->buttonGroup();
    }
}
