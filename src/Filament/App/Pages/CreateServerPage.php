<?php
namespace Smayt\UserGameServerCreator\Filament\App\Pages;
use App\Models\Allocation;
use App\Models\Egg;
use App\Models\Node;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Smayt\UserGameServerCreator\Models\EggSettings;
use Smayt\UserGameServerCreator\Models\UgscEggImage;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;
class CreateServerPage extends Page
{
    protected static ?string $slug = 'create-server/configure';
    protected static ?string $title = 'Configure Server';
    protected string $view = 'ugsc::create-server';
    protected static bool $shouldRegisterNavigation = false;
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'tabler-cube-plus';
    }
    public static function canAccess(): bool
    {
        return UserResourceLimits::where('user_id', auth()->id())->exists();
    }
    public function schema(Schema $schema): Schema
    {
        return $schema->components([]);
    }
    public function getViewData(): array
    {
        $eggId = request()->query('egg');
        $egg = Egg::findOrFail($eggId);
        $settings = EggSettings::forEgg($egg->id);
        $user = auth()->user();
        $limits = UserResourceLimits::where('user_id', $user->id)->first();
        $cpuLeft  = $limits?->getCpuLeft();
        $memLeft  = $limits?->getMemoryLeft();
        $diskLeft = $limits?->getDiskLeft();
        $showMapSize = in_array('steam:252490', $egg->tags ?? []);
        $ugscImage = UgscEggImage::forEgg($egg->id);
        $deploymentTags = array_filter(explode(',', config('user-game-server-creator.deployment_tags') ?? ''));
        $nodeQuery = Node::query()
            ->withSum('servers', 'memory')
            ->withSum('servers', 'disk')
            ->withSum('servers', 'cpu')
            ->where('public', true)
            ->orderBy('name');
        if (!empty($deploymentTags)) {
            $nodeQuery->where(function ($q) use ($deploymentTags) {
                foreach ($deploymentTags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }
        $nodes = $nodeQuery->get();
        // NOTE: Node declares public typed properties (servers_sum_cpu, etc.) that
        // shadow the dynamically eager-loaded withSum attributes, so we must read
        // the raw attribute array instead of the magic property accessor.
        $nodeResources = $nodes->mapWithKeys(function (Node $node) {
            $attrs = $node->getAttributes();
            $usedCpu    = (int) ($attrs['servers_sum_cpu'] ?? 0);
            $usedMemory = (int) ($attrs['servers_sum_memory'] ?? 0);
            $usedDisk   = (int) ($attrs['servers_sum_disk'] ?? 0);
            $cpuLimit    = ($node->cpu > 0 && $node->cpu_overallocate >= 0) ? $node->cpu * (1 + ($node->cpu_overallocate / 100)) : null;
            $memoryLimit = ($node->memory > 0 && $node->memory_overallocate >= 0) ? $node->memory * (1 + ($node->memory_overallocate / 100)) : null;
            $diskLimit   = ($node->disk > 0 && $node->disk_overallocate >= 0) ? $node->disk * (1 + ($node->disk_overallocate / 100)) : null;
            return [$node->id => [
                'name'         => $node->name,
                'free_cpu'     => $cpuLimit === null ? null : max(0, round($cpuLimit - $usedCpu)),
                'free_memory'  => $memoryLimit === null ? null : max(0, round($memoryLimit - $usedMemory)),
                'free_disk'    => $diskLimit === null ? null : max(0, round($diskLimit - $usedDisk)),
                // Raw values needed for client-side hard-cap (raw node capacity)
                // vs soft-warning (already-used by existing servers) logic.
                'raw_cpu'      => $node->cpu,
                'raw_memory'   => $node->memory,
                'raw_disk'     => $node->disk,
                'used_cpu'     => $usedCpu,
                'used_memory'  => $usedMemory,
                'used_disk'    => $usedDisk,
            ]];
        });
        $allocations = Allocation::whereNull('server_id')
            ->whereIn('node_id', $nodes->pluck('id'))
            ->orderBy('node_id')
            ->orderBy('port')
            ->get(['id', 'ip', 'port', 'node_id']);
        return [
            'egg'           => $egg,
            'settings'      => $settings,
            'cpuLeft'       => $cpuLeft,
            'memLeft'       => $memLeft,
            'diskLeft'      => $diskLeft,
            'showMapSize'   => $showMapSize,
            'allocations'   => $allocations,
            'bannerUrl'     => $ugscImage?->getBannerUrl(),
            'nodes'         => $nodes,
            'nodeResources' => $nodeResources,
        ];
    }
}
