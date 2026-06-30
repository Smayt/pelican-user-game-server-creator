<?php
namespace Smayt\UserGameServerCreator\Filament\Admin\Resources\Categories\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Smayt\UserGameServerCreator\Filament\Admin\Resources\Categories\CategoriesResource;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
