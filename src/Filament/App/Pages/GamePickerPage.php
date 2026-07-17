<?php
namespace Smayt\UserGameServerCreator\Filament\App\Pages;
use App\Models\Allocation;
use App\Models\Egg;
use App\Models\Node;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Smayt\UserGameServerCreator\Models\Category;
use Smayt\UserGameServerCreator\Models\EggSettings;
use Smayt\UserGameServerCreator\Models\UgscEggImage;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;
use Smayt\UserGameServerCreator\Services\DeploymentPortFilter;
class GamePickerPage extends Page
{
    protected static ?string $slug = 'create-server';
    protected static ?string $title = 'Create Server';
    protected string $view = 'ugsc::game-picker';
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'tabler-cube-plus';
    }
    public static function getNavigationLabel(): string
    {
        return 'Create Server';
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
        $user = auth()->user();
        $limits = UserResourceLimits::where('user_id', $user->id)->first();
        $categories = Category::orderBy('sort_order')->get();
        $ugscImages = UgscEggImage::all()->keyBy('egg_id');
        $eggs = Egg::all()->map(function (Egg $egg) use ($ugscImages) {
            $settings = EggSettings::where('egg_id', $egg->id)->first();
            if ($settings && $settings->hidden) {
                return null;
            }
            $ugscImage = $ugscImages->get($egg->id);
            return [
                'id'            => $egg->id,
                'name'          => $egg->name,
                'description'   => $egg->description,
                'icon'          => $ugscImage?->getGridUrl() ?? $ugscImage?->getListUrl() ?? null,
                'list_icon'     => $ugscImage?->getListUrl() ?? null,
                'grid_icon'     => $ugscImage?->getGridUrl() ?? null,
                'category_slug' => $settings?->category?->slug,
                'popular'       => $settings?->popular ?? false,
            ];
        })->filter()->values();
        $budgetCpu    = $limits?->getCpuLeft() !== null ? $limits->getCpuLeft() . '%' : 'Unlimited';
        $budgetMemory = $limits?->getMemoryLeft() !== null ? $limits->getMemoryLeft() . ' MiB' : 'Unlimited';
        $budgetDisk   = $limits?->getDiskLeft() !== null ? $limits->getDiskLeft() . ' MiB' : 'Unlimited';
        $deploymentTags = array_filter(explode(',', config('user-game-server-creator.deployment_tags') ?? ''));
        $nodeQuery = Node::query()
            ->withSum('servers', 'memory')
            ->withSum('servers', 'disk')
            ->withSum('servers', 'cpu')
            ->where('public', true);
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
        $totalFreeCpu = $nodes->sum(function (Node $node) {
            $used = (int) ($node->getAttributes()['servers_sum_cpu'] ?? 0);
            if ($node->cpu <= 0 || $node->cpu_overallocate < 0) return 0;
            $limit = $node->cpu * (1 + ($node->cpu_overallocate / 100));
            return max(0, $limit - $used);
        });
        $totalFreeMemory = $nodes->sum(function (Node $node) {
            $used = (int) ($node->getAttributes()['servers_sum_memory'] ?? 0);
            if ($node->memory <= 0 || $node->memory_overallocate < 0) return 0;
            $limit = $node->memory * (1 + ($node->memory_overallocate / 100));
            return max(0, $limit - $used);
        });
        $totalFreeDisk = $nodes->sum(function (Node $node) {
            $used = (int) ($node->getAttributes()['servers_sum_disk'] ?? 0);
            if ($node->disk <= 0 || $node->disk_overallocate < 0) return 0;
            $limit = $node->disk * (1 + ($node->disk_overallocate / 100));
            return max(0, $limit - $used);
        });
        $eligibleNodeIds = $nodes->pluck('id')->all();
        $portRangesByNode = DeploymentPortFilter::rangesByNode($eligibleNodeIds);
        $totalFreePorts = DeploymentPortFilter::applyPerNodeToQuery(
            Allocation::whereNull('server_id')->whereIn('node_id', $eligibleNodeIds),
            $eligibleNodeIds,
            $portRangesByNode
        )->count();
        return [
            'categories'      => $categories,
            'eggs'            => $eggs,
            'budgetCpu'       => $budgetCpu,
            'budgetMemory'    => $budgetMemory,
            'budgetDisk'      => $budgetDisk,
            'totalFreeCpu'    => round($totalFreeCpu) . '%',
            'totalFreeMemory' => round($totalFreeMemory) . ' MiB',
            'totalFreeDisk'   => round($totalFreeDisk) . ' MiB',
            'totalFreePorts'  => $totalFreePorts,
        ];
    }
}
