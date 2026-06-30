<?php
namespace Smayt\UserGameServerCreator\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $icon
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Category extends Model
{
    protected $table = 'ugsc_categories';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (blank($category->slug) && filled($category->name)) {
                $category->slug = (string) str($category->name)->slug();
            }
        });
    }

    public function eggSettings(): HasMany
    {
        return $this->hasMany(EggSettings::class, 'category_id');
    }
}
