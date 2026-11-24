<?php

namespace App\Filament\Resources\LiveEventResource\Pages;

use App\Filament\Resources\LiveEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLiveEvent extends EditRecord
{
    protected static string $resource = LiveEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
