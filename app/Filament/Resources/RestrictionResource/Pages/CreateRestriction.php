<?php

namespace App\Filament\Resources\RestrictionResource\Pages;

use App\Filament\Resources\RestrictionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestriction extends CreateRecord
{
    protected static string $resource = RestrictionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
