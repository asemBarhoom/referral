<?php

namespace App\Filament\Resources\RestrictionResource\Pages;

use App\Filament\Resources\RestrictionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRestriction extends EditRecord
{
    protected static string $resource = RestrictionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
