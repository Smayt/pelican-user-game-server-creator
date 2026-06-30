<?php
namespace Smayt\UserGameServerCreator\Filament\Components\Actions;

use Filament\Actions\Action;
use Smayt\UserGameServerCreator\Filament\App\Pages\GamePickerPage;
use Smayt\UserGameServerCreator\Models\UserResourceLimits;

class CreateServerAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'ugsc_create_server';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Create Server');
        $this->visible(fn () => UserResourceLimits::where('user_id', auth()->id())->exists());
        $this->url(fn () => GamePickerPage::getUrl());
    }
}
