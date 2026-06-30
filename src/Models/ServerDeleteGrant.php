<?php
namespace Smayt\UserGameServerCreator\Models;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerDeleteGrant extends Model
{
    protected $table = 'ugsc_server_delete_grants';

    protected $fillable = ['user_id', 'server_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function isGranted(int $userId, int $serverId): bool
    {
        return self::where('user_id', $userId)->where('server_id', $serverId)->exists();
    }

    public static function grant(int $userId, int $serverId): void
    {
        self::firstOrCreate(['user_id' => $userId, 'server_id' => $serverId]);
    }

    public static function revoke(int $userId, int $serverId): void
    {
        self::where('user_id', $userId)->where('server_id', $serverId)->delete();
    }

    public static function grantAllVisibleToUser(int $userId): int
    {
        // Grant delete on every server this user has subuser access to
        $serverIds = \App\Models\Subuser::where('user_id', $userId)->pluck('server_id');
        if ($serverIds->isEmpty()) {
            return 0;
        }
        $now = now();
        self::insertOrIgnore(
            $serverIds->map(fn ($serverId) => [
                'user_id'    => $userId,
                'server_id'  => $serverId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
        return $serverIds->count();
    }

    public static function revokeAllFromUser(int $userId): int
    {
        return self::where('user_id', $userId)->delete();
    }
}
