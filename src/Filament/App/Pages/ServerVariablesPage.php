<?php
namespace Smayt\UserGameServerCreator\Filament\App\Pages;

use App\Models\Allocation;
use App\Models\Egg;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;

class ServerVariablesPage extends Page
{
    protected static ?string $slug = 'create-server/variables';
    protected static ?string $title = 'Server Variables';
    protected string $view = 'ugsc::server-variables';
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

        $name          = (string) request()->query('name', '');
        $cpu           = (int) request()->query('cpu', 0);
        $memory        = (int) request()->query('memory', 0);
        $disk          = (int) request()->query('disk', 0);
        $allocationId  = (int) request()->query('allocation_id', 0);
        $nodeId        = (int) request()->query('node_id', 0);
        $players       = (int) request()->query('players', 0);
        $mapSize       = (int) request()->query('map_size', 0);

        $allocation = Allocation::whereNull('server_id')->find($allocationId);
        if (!$allocation) {
            abort(422, 'That allocation is no longer available. Please go back and try again.');
        }

        $editableVariables = [];
        $hiddenVariables = [];

        foreach ($egg->variables as $variable) {
            $rules = is_array($variable->rules) ? $variable->rules : (json_decode($variable->rules, true) ?? []);
            $isRequired = in_array('required', $rules, true);
            $hasDefault = $variable->default_value !== null && $variable->default_value !== '';

            $pillLabel = '{' . '{' . $variable->env_variable . '}' . '}';

            if ($variable->user_editable) {
                $editableVariables[] = [
                    'env_variable' => $variable->env_variable,
                    'pill'         => $pillLabel,
                    'name'         => $variable->name,
                    'description'  => $variable->description,
                    'default'      => $variable->default_value,
                    'required'     => $isRequired,
                    'rules'        => $rules,
                    'fallback'     => false,
                ];
            } elseif (!$hasDefault && $isRequired) {
                // Admin-only/hidden variable, but no usable default and creation
                // would fail without a value. Surface it anyway, flagged distinctly.
                $editableVariables[] = [
                    'env_variable' => $variable->env_variable,
                    'pill'         => $pillLabel,
                    'name'         => $variable->name,
                    'description'  => $variable->description,
                    'default'      => '',
                    'required'     => $isRequired,
                    'rules'        => $rules,
                    'fallback'     => true,
                ];
            } else {
                $hiddenVariables[] = $variable->env_variable;
            }
        }

        return [
            'egg'               => $egg,
            'editableVariables' => $editableVariables,
            'hiddenVariables'   => $hiddenVariables,
            'name'              => $name,
            'cpu'               => $cpu,
            'memory'            => $memory,
            'disk'              => $disk,
            'allocationId'      => $allocationId,
            'nodeId'            => $nodeId,
            'players'           => $players,
            'mapSize'           => $mapSize,
            'allocationLabel'   => $allocation->ip . ':' . $allocation->port,
        ];
    }
}
