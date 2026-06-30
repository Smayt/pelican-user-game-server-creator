<?php
namespace Smayt\UserGameServerCreator\Models;

use App\Models\Egg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UgscEggImage extends Model
{
    protected $table = 'ugsc_egg_images';

    protected $fillable = [
        'egg_id',
        'steam_app_id',
        'grid_path',
        'banner_path',
        'list_path',
        'grid_protected',
        'banner_protected',
        'list_protected',
    ];

    protected $casts = [
        'grid_protected'   => 'boolean',
        'banner_protected' => 'boolean',
        'list_protected'   => 'boolean',
    ];

    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }

    public function getGridUrl(): ?string
    {
        return $this->grid_path ? Storage::disk('public')->url($this->grid_path) : null;
    }

    public function getBannerUrl(): ?string
    {
        return $this->banner_path ? Storage::disk('public')->url($this->banner_path) : null;
    }

    public function getListUrl(): ?string
    {
        return $this->list_path ? Storage::disk('public')->url($this->list_path) : null;
    }

    public function isProtected(string $type): bool
    {
        return (bool) $this->{"{$type}_protected"};
    }

    public function setProtected(string $type, bool $value = true): void
    {
        $this->{"{$type}_protected"} = $value;
        $this->save();
    }

    public static function forEgg(int $eggId): ?self
    {
        return self::where('egg_id', $eggId)->first();
    }

    public static function forEggOrNew(int $eggId): self
    {
        return self::firstOrNew(['egg_id' => $eggId]);
    }
}
