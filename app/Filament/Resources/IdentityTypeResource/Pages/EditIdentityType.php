<?php

namespace App\Filament\Resources\IdentityTypeResource\Pages;

use App\Filament\Resources\IdentityTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIdentityType extends EditRecord
{
    protected static string $resource = IdentityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()
            ->visible(fn() => !$this->record->is_system)];
    }
}
