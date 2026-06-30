<?php
namespace Smayt\UserGameServerCreator\Models;

use App\Models\Egg;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $egg_id
 * @property ?int $category_id
 * @property bool $hidden
 * @property bool $slots_mode
 * @property bool $popular
 * @property int $ram_base
 * @property int $ram_max
 * @property int $cpu_base
 * @property int $cpu_max
 * @property int $disk
 * @property int $min_players
 * @property int $max_players
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class EggSettings extends Model
{
    protected $table = 'ugsc_egg_settings';

    protected $fillable = [
        'egg_id',
        'category_id',
        'hidden',
        'slots_mode',
        'popular',
        'ram_base',
        'ram_max',
        'cpu_base',
        'cpu_max',
        'disk',
        'min_players',
        'max_players',
    ];

    protected $casts = [
        'hidden'     => 'boolean',
        'slots_mode' => 'boolean',
        'popular'    => 'boolean',
    ];

    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getRamForPlayers(int $players): int
    {
        if ($this->min_players >= $this->max_players) {
            return $this->ram_base;
        }
        $t = ($players - $this->min_players) / ($this->max_players - $this->min_players);
        $t = max(0, min(1, $t));
        return (int) round($this->ram_base + ($this->ram_max - $this->ram_base) * $t);
    }

    public function getCpuForPlayers(int $players): int
    {
        if ($this->min_players >= $this->max_players) {
            return $this->cpu_base;
        }
        $t = ($players - $this->min_players) / ($this->max_players - $this->min_players);
        $t = max(0, min(1, $t));
        return (int) round($this->cpu_base + ($this->cpu_max - $this->cpu_base) * $t);
    }

    public static function forEgg(int $eggId): self
    {
        return self::firstOrCreate(
            ['egg_id' => $eggId],
            [
                'hidden'     => false,
                'slots_mode' => false,
                'popular'    => false,
                'ram_base'   => 1024,
                'ram_max'    => 4096,
                'cpu_base'   => 100,
                'cpu_max'    => 200,
                'disk'       => 10240,
                'min_players' => 2,
                'max_players' => 10,
            ]
        );
    }
}
