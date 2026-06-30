<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Resources\EggSettings\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Smayt\UserGameServerCreator\Filament\Admin\Resources\EggSettings\EggSettingsResource;

class ManageEggSettings extends ManageRecords
{
    protected static string $resource = EggSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
