<?php

namespace App\Filament\Resources\IdentityTypeResource\Pages;

use App\Filament\Resources\IdentityTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIdentityTypes extends ListRecords
{
    protected static string $resource = IdentityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة نوع هوية')];
    }
}
