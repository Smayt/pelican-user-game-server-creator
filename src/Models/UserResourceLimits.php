<?php
namespace Smayt\UserGameServerCreator\Models;

use App\Models\Egg;
use App\Models\Server;
use App\Models\User;
use App\Services\Servers\ServerCreationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $cpu
 * @property int $memory
 * @property int $disk
 * @property ?int $server_limit
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UserResourceLimits extends Model
{
    protected $table = 'ugsc_user_resource_limits';

    protected $fillable = [
        'user_id',
        'cpu',
        'memory',
        'disk',
        'server_limit',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCpuLeft(): ?int
    {
        if ($this->cpu > 0) {
            return max(0, $this->cpu - $this->user->servers->sum('cpu'));
        }
        return null;
    }

    public function getMemoryLeft(): ?int
    {
        if ($this->memory > 0) {
            return max(0, $this->memory - $this->user->servers->sum('memory'));
        }
        return null;
    }

    public function getDiskLeft(): ?int
    {
        if ($this->disk > 0) {
            return max(0, $this->disk - $this->user->servers->sum('disk'));
        }
        return null;
    }

    public function getServerLimitLeft(): ?int
    {
        if ($this->server_limit > 0) {
            return max(0, $this->server_limit - $this->user->servers->count());
        }
        return null;
    }

    public function canCreateServer(int $cpu, int $memory, int $disk): bool
    {
        if ($this->server_limit > 0 && $this->user->servers->count() >= $this->server_limit) {
            return false;
        }

        if ($this->cpu > 0) {
            if ($cpu <= 0) return false;
            if ($this->user->servers->sum('cpu') + $cpu > $this->cpu) return false;
        }

        if ($this->memory > 0) {
            if ($memory <= 0) return false;
            if ($this->user->servers->sum('memory') + $memory > $this->memory) return false;
        }

        if ($this->disk > 0) {
            if ($disk <= 0) return false;
            if ($this->user->servers->sum('disk') + $disk > $this->disk) return false;
        }

        return true;
    }

    public function createServer(string $name, int|Egg $egg, int $cpu, int $memory, int $disk, array $variables = [], int $allocationId = 0, int $nodeId = 0): Server|bool
    {
        if (!$this->canCreateServer($cpu, $memory, $disk)) {
            return false;
        }

        if (!$egg instanceof Egg) {
            $egg = Egg::findOrFail($egg);
        }

        $environment = [];
        foreach ($egg->variables as $variable) {
            $environment[$variable->env_variable] = $variables[$variable->env_variable] ?? $variable->default_value;
        }

        $data = [
            'name'             => $name,
            'owner_id'         => $this->user_id,
            'egg_id'           => $egg->id,
            'allocation_id'    => $allocationId,
            'node_id'          => $nodeId,
            'cpu'              => $cpu,
            'memory'           => $memory,
            'disk'             => $disk,
            'swap'             => 0,
            'io'               => 500,
            'environment'      => $environment,
            'skip_scripts'     => false,
            'start_on_completion' => true,
            'oom_killer'       => false,
            'database_limit'   => config('user-game-server-creator.database_limit', 0),
            'allocation_limit' => config('user-game-server-creator.allocation_limit', 1),
            'backup_limit'     => config('user-game-server-creator.backup_limit', 1),
        ];

        /** @var ServerCreationService $service */
        $service = app(ServerCreationService::class);

        return $service->handle($data);
    }
}
