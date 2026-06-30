<?php
namespace Smayt\UserGameServerCreator\Http\Controllers;
use App\Enums\SubuserPermission;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Subuser;
use App\Services\Subusers\SubuserCreationService;
use App\Services\Subusers\SubuserDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Smayt\UserGameServerCreator\Models\ServerDeleteGrant;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;

class PermissionsController extends Controller
{
    public function save(Request $request): JsonResponse
    {
        if (!auth()->user()->isRootAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'limit_id'   => 'required|integer|exists:ugsc_user_resource_limits,id',
            'visible'    => 'array',
            'deletable'  => 'array',
        ]);

        $limit = UserResourceLimits::findOrFail($validated['limit_id']);
        $userId = $limit->user_id;
        $user = $limit->user;

        $visibleIds = array_map('intval', $validated['visible'] ?? []);
        $deletableIds = array_intersect(array_map('intval', $validated['deletable'] ?? []), $visibleIds);

        $currentSubuserServerIds = Subuser::where('user_id', $userId)->pluck('server_id')->toArray();
        $toAdd = array_diff($visibleIds, $currentSubuserServerIds);
        $toRemove = array_diff($currentSubuserServerIds, $visibleIds);

        $creationService = app(SubuserCreationService::class);
        $deletionService = app(SubuserDeletionService::class);

        foreach ($toAdd as $serverId) {
            $server = Server::find($serverId);
            if ($server) {
                try {
                    $creationService->handle($server, $user->email, [
                        SubuserPermission::ControlConsole->value,
                        SubuserPermission::ControlStart->value,
                        SubuserPermission::ControlStop->value,
                        SubuserPermission::ControlRestart->value,
                        SubuserPermission::WebsocketConnect->value,
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('UGSC: failed to create subuser', ['server_id' => $serverId, 'user' => $user->email, 'error' => $e->getMessage()]);
                }
            }
        }

        foreach ($toRemove as $serverId) {
            $subuser = Subuser::where('user_id', $userId)->where('server_id', $serverId)->first();
            $server = Server::find($serverId);
            if ($subuser && $server) {
                $deletionService->handle($subuser, $server);
            }
            ServerDeleteGrant::revoke($userId, $serverId);
        }

        ServerDeleteGrant::where('user_id', $userId)->delete();
        foreach ($deletableIds as $serverId) {
            ServerDeleteGrant::grant($userId, $serverId);
        }

        return response()->json(['success' => true]);
    }
}
