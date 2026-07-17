<?php
namespace Smayt\UserGameServerCreator\Models;

use App\Models\Node;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $node_id
 * @property string $ports
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class NodePortRange extends Model
{
    protected $table = 'ugsc_node_port_ranges';

    protected $fillable = [
        'node_id',
        'ports',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
