<?php
namespace Smayt\UserGameServerCreator;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Section;

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
                    TagsInput::make('deployment_ports')
                        ->label('Ports')
                        ->placeholder('New port or port range')
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip('Ports to use for deployment. Leave empty for any allocation.')
                        ->default(fn () => array_filter(explode(',', config('user-game-server-creator.deployment_ports')))),
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
            'UGSC_DEPLOYMENT_PORTS' => implode(',', $data['deployment_ports'] ?? []),
        ]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
