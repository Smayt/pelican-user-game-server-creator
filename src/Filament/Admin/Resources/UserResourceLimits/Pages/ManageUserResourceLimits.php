<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Resources\UserResourceLimits\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Smayt\UserGameServerCreator\Filament\Admin\Resources\UserResourceLimits\UserResourceLimitsResource;

class ManageUserResourceLimits extends ManageRecords
{
    protected static string $resource = UserResourceLimitsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
