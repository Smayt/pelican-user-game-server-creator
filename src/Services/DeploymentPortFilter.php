<?php
namespace Smayt\UserGameServerCreator\Services;

use Illuminate\Database\Eloquent\Builder;
use Smayt\UserGameServerCreator\Models\NodePortRange;

class DeploymentPortFilter
{
    /**
     * Parse a comma-separated list of ports/ranges (e.g. "25565,27015-27020")
     * into a list of [min, max] tuples.
     */
    public static function parseRanges(?string $raw): array
    {
        $entries = array_filter(array_map('trim', explode(',', (string) $raw)));
        $ranges = [];
        foreach ($entries as $entry) {
            if (str_contains($entry, '-')) {
                [$min, $max] = array_pad(array_map('intval', explode('-', $entry, 2)), 2, 0);
                if ($min > 0 && $max >= $min) {
                    $ranges[] = [$min, $max];
                }
            } elseif (ctype_digit($entry)) {
                $port = (int) $entry;
                $ranges[] = [$port, $port];
            }
        }
        return $ranges;
    }

    public static function matches(array $ranges, int $port): bool
    {
        if (empty($ranges)) {
            return true;
        }
        foreach ($ranges as [$min, $max]) {
            if ($port >= $min && $port <= $max) {
                return true;
            }
        }
        return false;
    }

    public static function applyToQuery(Builder $query, array $ranges): Builder
    {
        if (empty($ranges)) {
            return $query;
        }
        return $query->where(function (Builder $q) use ($ranges) {
            foreach ($ranges as [$min, $max]) {
                $q->orWhereBetween('port', [$min, $max]);
            }
        });
    }

    /**
     * Configured port ranges for a single node. Empty array = unrestricted.
     */
    public static function rangesForNode(int $nodeId): array
    {
        $row = NodePortRange::query()->where('node_id', $nodeId)->first();
        return $row ? self::parseRanges($row->ports) : [];
    }

    /**
     * Configured port ranges for a set of nodes, keyed by node_id. Nodes with
     * no row (or a blank ports value) are omitted, i.e. left unrestricted.
     */
    public static function rangesByNode(iterable $nodeIds): array
    {
        return NodePortRange::query()
            ->whereIn('node_id', $nodeIds)
            ->get(['node_id', 'ports'])
            ->mapWithKeys(fn (NodePortRange $row) => [$row->node_id => self::parseRanges($row->ports)])
            ->filter(fn (array $ranges) => !empty($ranges))
            ->all();
    }

    /**
     * Scope an allocations query so each node only offers ports within its own
     * configured range; nodes absent from $rangesByNode are left unrestricted.
     *
     * @param array $eligibleNodeIds All node ids the query is already scoped to.
     * @param array $rangesByNode    [node_id => [[min, max], ...]] from rangesByNode().
     */
    public static function applyPerNodeToQuery(Builder $query, array $eligibleNodeIds, array $rangesByNode): Builder
    {
        if (empty($rangesByNode)) {
            return $query;
        }

        $unrestrictedNodeIds = array_values(array_diff($eligibleNodeIds, array_keys($rangesByNode)));

        return $query->where(function (Builder $q) use ($rangesByNode, $unrestrictedNodeIds) {
            foreach ($rangesByNode as $nodeId => $ranges) {
                $q->orWhere(function (Builder $q2) use ($nodeId, $ranges) {
                    $q2->where('node_id', $nodeId);
                    self::applyToQuery($q2, $ranges);
                });
            }
            if (!empty($unrestrictedNodeIds)) {
                $q->orWhereIn('node_id', $unrestrictedNodeIds);
            }
        });
    }
}
