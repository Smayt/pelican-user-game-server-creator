<?php
namespace Smayt\UserGameServerCreator\Providers;
use App\Enums\HeaderActionPosition;
use App\Filament\App\Resources\Servers\Pages\ListServers;
use App\Filament\Server\Pages\Console;
use Illuminate\Support\ServiceProvider;
use Smayt\UserGameServerCreator\Filament\Components\Actions\CreateServerAction;
use Smayt\UserGameServerCreator\Filament\Components\Actions\DeleteServerAction;
class UserGameServerCreatorPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            plugin_path('user-game-server-creator', 'config/user-game-server-creator.php'),
            'user-game-server-creator'
        );
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            plugin_path('user-game-server-creator', 'database/migrations')
        );
        $this->loadViewsFrom(
            plugin_path('user-game-server-creator', 'resources/views'),
            'ugsc'
        );
        ListServers::registerCustomHeaderActions(HeaderActionPosition::Before, CreateServerAction::make());
        Console::registerCustomHeaderActions(HeaderActionPosition::After, DeleteServerAction::make());
    }
}
