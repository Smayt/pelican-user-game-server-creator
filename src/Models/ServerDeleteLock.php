<?php
namespace Smayt\UserGameServerCreator\Models;

use App\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerDeleteLock extends Model
{
    protected $table = 'ugsc_server_delete_locks';

    protected $fillable = ['server_id'];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function isLocked(int $serverId): bool
    {
        return self::where('server_id', $serverId)->exists();
    }

    public static function lock(int $serverId): void
    {
        self::firstOrCreate(['server_id' => $serverId]);
    }

    public static function unlock(int $serverId): void
    {
        self::where('server_id', $serverId)->delete();
    }

    public static function toggle(int $serverId): bool
    {
        if (self::isLocked($serverId)) {
            self::unlock($serverId);
            return false;
        }
        self::lock($serverId);
        return true;
    }
}
