<?php
namespace Smayt\UserGameServerCreator\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\Servers\ServerDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Smayt\UserGameServerCreator\Models\ServerDeleteGrant;
use Smayt\UserGameServerCreator\Models\ServerDeleteLock;

class ServerDeletionController extends Controller
{
    public function destroy(Request $request, int $serverId): JsonResponse
    {
        $user = auth()->user();
        $server = Server::find($serverId);

        if (!$server) {
            return response()->json(['success' => false, 'message' => 'Server not found.'], 404);
        }

        if (!$user->isRootAdmin()) {
            $isOwner = $server->owner_id === $user->id;

            if ($isOwner) {
                if (ServerDeleteLock::isLocked($server->id)) {
                    return response()->json(['success' => false, 'message' => 'This server has been locked from deletion by an administrator.'], 403);
                }
            } else {
                if (!ServerDeleteGrant::isGranted($user->id, $server->id)) {
                    return response()->json(['success' => false, 'message' => 'You do not have permission to delete this server.'], 403);
                }
            }
        }

        try {
            app(ServerDeletionService::class)->handle($server);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
