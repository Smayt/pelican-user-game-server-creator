<?php
namespace Smayt\UserGameServerCreator\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class UserGameServerCreatorRoutesProvider extends RouteServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware(['web', 'auth'])
                ->group(plugin_path('user-game-server-creator', 'routes/web.php'));
        });
    }
}
