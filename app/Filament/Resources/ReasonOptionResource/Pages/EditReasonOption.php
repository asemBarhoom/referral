<?php

namespace App\Filament\Resources\ReasonOptionResource\Pages;

use App\Filament\Resources\ReasonOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReasonOption extends EditRecord
{
    protected static string $resource = ReasonOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
