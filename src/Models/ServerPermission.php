<?php
namespace Smayt\UserGameServerCreator\Models;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerPermission extends Model
{
    protected $table = 'ugsc_server_permissions';

    protected $fillable = [
        'user_id',
        'server_id',
        'can_view',
        'can_delete',
    ];

    protected $casts = [
        'can_view'   => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function grant(int $userId, int $serverId, bool $view = true, bool $delete = false): self
    {
        return self::updateOrCreate(
            ['user_id' => $userId, 'server_id' => $serverId],
            ['can_view' => $view, 'can_delete' => $delete]
        );
    }

    public static function canView(int $userId, int $serverId): bool
    {
        return self::where('user_id', $userId)
            ->where('server_id', $serverId)
            ->where('can_view', true)
            ->exists();
    }

    public static function canDelete(int $userId, int $serverId): bool
    {
        return self::where('user_id', $userId)
            ->where('server_id', $serverId)
            ->where('can_delete', true)
            ->exists();
    }

    public static function viewableServerIds(int $userId): array
    {
        return self::where('user_id', $userId)
            ->where('can_view', true)
            ->pluck('server_id')
            ->toArray();
    }

    public static function deletableServerIds(int $userId): array
    {
        return self::where('user_id', $userId)
            ->where('can_delete', true)
            ->pluck('server_id')
            ->toArray();
    }

    public static function grantAllToUser(int $userId, bool $view = true, bool $delete = false): int
    {
        $serverIds = Server::pluck('id');
        $count = 0;
        foreach ($serverIds as $serverId) {
            self::grant($userId, $serverId, $view, $delete);
            $count++;
        }
        return $count;
    }

    public static function revokeAllFromUser(int $userId): int
    {
        return self::where('user_id', $userId)->delete();
    }
}
