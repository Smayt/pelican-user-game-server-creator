<?php
namespace Smayt\UserGameServerCreator\Http\Controllers;
use App\Filament\Server\Pages\Console;
use App\Http\Controllers\Controller;
use App\Models\Allocation;
use App\Models\Node;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;
class ServerCreationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'egg_id'              => 'required|integer|exists:eggs,id',
            'cpu'                 => 'required|integer|min:1',
            'memory'              => 'required|integer|min:1',
            'disk'                => 'required|integer|min:1',
            'allocation_id'       => 'required|integer|exists:allocations,id',
            'confirm_overallocate'=> 'sometimes|boolean',
            'variables'           => 'sometimes|array',
        ]);

        $submittedVariables = $validated['variables'] ?? [];
        $limits = UserResourceLimits::where('user_id', auth()->id())->first();
        if (!$limits) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to create servers.'], 403);
        }
        if (!$limits->canCreateServer($validated['cpu'], $validated['memory'], $validated['disk'])) {
            return response()->json(['success' => false, 'message' => 'You have exceeded your resource limits.'], 403);
        }
        $allocation = Allocation::whereNull('server_id')->find($validated['allocation_id']);
        if (!$allocation) {
            return response()->json(['success' => false, 'message' => 'That allocation is no longer available. Please go back and try again.'], 422);
        }
        $node = Node::query()
            ->withSum('servers', 'memory')
            ->withSum('servers', 'disk')
            ->withSum('servers', 'cpu')
            ->where('public', true)
            ->find($allocation->node_id);
        if (!$node) {
            return response()->json([
                'success' => false,
                'message' => 'That node does not have enough physical capacity for this server. Try a smaller configuration or a different node.',
            ], 422);
        }

        $deploymentTags = array_filter(explode(',', config('user-game-server-creator.deployment_tags') ?? ''));
        if (!empty($deploymentTags)) {
            $nodeTags = $node->tags ?? [];
            if (collect($nodeTags)->intersect($deploymentTags)->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'That node is not available for user server creation.',
                ], 422);
            }
        }

        $check = $this->checkNodeCapacity($node, $validated['memory'], $validated['disk'], $validated['cpu']);

        if ($check === 'hard') {
            return response()->json([
                'success' => false,
                'message' => 'That node does not have enough physical capacity for this server. Try a smaller configuration or a different node.',
            ], 422);
        }

        if ($check === 'disk_free') {
            return response()->json([
                'success' => false,
                'message' => 'That node does not have enough free disk space for this server. Disk cannot be overallocated. Try a smaller disk size or a different node.',
            ], 422);
        }

        if ($check === 'soft' && empty($validated['confirm_overallocate'])) {
            return response()->json([
                'success'  => false,
                'warning'  => true,
                'message'  => 'This will allocate more CPU or memory than the node currently has reserved for its other servers (though it still has enough raw capacity). If all servers run at full load simultaneously, performance may suffer.',
            ], 200);
        }
        try {
            $server = $limits->createServer(
                $validated['name'],
                $validated['egg_id'],
                $validated['cpu'],
                $validated['memory'],
                $validated['disk'],
                $submittedVariables,
                $allocation->id,
                $allocation->node_id,
            );
            if (!$server) {
                return response()->json(['success' => false, 'message' => 'Failed to create server.']);
            }
            $redirect = Console::getUrl(panel: 'server', tenant: $server);
            return response()->json(['success' => true, 'redirect' => $redirect]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Capacity policy (per-resource, single new request compared against the
     * node's flat totals — NOT against used+requested):
     *
     *   CPU / Memory:
     *     - requested > raw            -> 'hard'   (always blocked, no override)
     *     - requested > (raw - used)   -> 'soft'   (confirm-to-proceed)
     *     - otherwise                  -> ok
     *
     *   Disk:
     *     - requested > (raw - used)   -> 'disk_free' (always blocked, no override —
     *       disk can never be overallocated, so this is disk's hard wall, not a soft tier)
     *
     * Returns the first violation found, checked in order: cpu hard, memory hard,
     * disk free, cpu soft, memory soft, else 'ok'.
     *
     * Reads withSum aggregates from the raw attribute array, since Node declares
     * public typed properties (servers_sum_cpu, etc.) that shadow the dynamically
     * eager-loaded withSum attributes via PHP's native property resolution, making
     * the magic-property values always read as 0.
     */
    private function checkNodeCapacity(Node $node, int $memory, int $disk, int $cpu): string
    {
        $attrs = $node->getAttributes();
        $usedMemory = (int) ($attrs['servers_sum_memory'] ?? 0);
        $usedDisk   = (int) ($attrs['servers_sum_disk'] ?? 0);
        $usedCpu    = (int) ($attrs['servers_sum_cpu'] ?? 0);

        if ($node->cpu > 0 && $cpu > $node->cpu) {
            return 'hard';
        }
        if ($node->memory > 0 && $memory > $node->memory) {
            return 'hard';
        }
        if ($node->disk > 0 && $disk > ($node->disk - $usedDisk)) {
            return 'disk_free';
        }
        if ($node->cpu > 0 && $cpu > ($node->cpu - $usedCpu)) {
            return 'soft';
        }
        if ($node->memory > 0 && $memory > ($node->memory - $usedMemory)) {
            return 'soft';
        }
        return 'ok';
    }
}
