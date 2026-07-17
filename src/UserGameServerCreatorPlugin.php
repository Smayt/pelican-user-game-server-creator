<?php
namespace Smayt\UserGameServerCreator;

use App\Contracts\Plugins\HasPluginSettings;
use App\Models\Node;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Smayt\UserGameServerCreator\Models\NodePortRange;

class UserGameServerCreatorPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'user-game-server-creator';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();

        $panel->discoverPages(
            plugin_path($this->getId(), "src/Filament/{$id}/Pages"),
            "Smayt\\UserGameServerCreator\\Filament\\{$id}\\Pages"
        );
        $panel->discoverResources(
            plugin_path($this->getId(), "src/Filament/{$id}/Resources"),
            "Smayt\\UserGameServerCreator\\Filament\\{$id}\\Resources"
        );
    }

    public function boot(Panel $panel): void
    {
        \Illuminate\Support\Facades\View::addNamespace(
            'ugsc',
            plugin_path($this->getId(), 'resources/views')
        );
    }

    public function getSettingsFormData(): array
    {
        return [
            'database_limit'   => config('user-game-server-creator.database_limit'),
            'allocation_limit' => config('user-game-server-creator.allocation_limit'),
            'backup_limit'     => config('user-game-server-creator.backup_limit'),
            'deployment_tags'  => array_filter(explode(',', config('user-game-server-creator.deployment_tags'))),
            'node_port_ranges' => self::nodePortRangesFormData(),
        ];
    }

    private static function nodePortRangesFormData(): array
    {
        return NodePortRange::query()->get()->map(fn (NodePortRange $range) => [
            'node_id' => $range->node_id,
            'ports'   => array_filter(explode(',', $range->ports)),
        ])->values()->all();
    }

    public function getSettingsForm(): array
    {
        return [
            Section::make('Deployment Settings')
                ->columns(2)
                ->schema([
                    TagsInput::make('deployment_tags')
                        ->label('Node tags')
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('Only nodes with these tags will be used. Leave empty to allow all nodes.')
                        ->default(fn () => array_filter(explode(',', config('user-game-server-creator.deployment_tags')))),
                    Repeater::make('node_port_ranges')
                        ->label('Per-node port ranges')
                        ->addActionLabel('Add node')
                        ->columnSpanFull()
                        ->columns(2)
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('Restrict which ports are offered per node. Nodes left unconfigured allow any allocation.')
                        ->schema([
                            Select::make('node_id')
                                ->label('Node')
                                ->options(fn () => Node::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            TagsInput::make('ports')
                                ->label('Ports')
                                ->placeholder('New port or port range'),
                        ])
                        ->default(fn () => self::nodePortRangesFormData()),
                ]),
            Section::make('Default Server Limits')
                ->columns(3)
                ->schema([
                    TextInput::make('database_limit')
                        ->label('Database limit')
                        ->numeric()
                        ->minValue(0)
                        ->default(fn () => config('user-game-server-creator.database_limit')),
                    TextInput::make('allocation_limit')
                        ->label('Allocation limit')
                        ->numeric()
                        ->minValue(0)
                        ->default(fn () => config('user-game-server-creator.allocation_limit')),
                    TextInput::make('backup_limit')
                        ->label('Backup limit')
                        ->numeric()
                        ->minValue(0)
                        ->default(fn () => config('user-game-server-creator.backup_limit')),
                ]),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'UGSC_DATABASE_LIMIT'   => $data['database_limit'] ?? 0,
            'UGSC_ALLOCATION_LIMIT' => $data['allocation_limit'] ?? 1,
            'UGSC_BACKUP_LIMIT'     => $data['backup_limit'] ?? 1,
            'UGSC_DEPLOYMENT_TAGS'  => implode(',', $data['deployment_tags'] ?? []),
        ]);

        $submittedNodeIds = [];
        foreach ($data['node_port_ranges'] ?? [] as $row) {
            $nodeId = (int) ($row['node_id'] ?? 0);
            if ($nodeId <= 0) {
                continue;
            }
            $submittedNodeIds[] = $nodeId;
            NodePortRange::updateOrCreate(
                ['node_id' => $nodeId],
                ['ports' => implode(',', $row['ports'] ?? [])]
            );
        }
        if (empty($submittedNodeIds)) {
            NodePortRange::query()->delete();
        } else {
            NodePortRange::query()->whereNotIn('node_id', $submittedNodeIds)->delete();
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
