<?php

namespace App\Filament\Resources\RestrictionResource\Pages;

use App\Filament\Resources\RestrictionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestrictions extends ListRecords
{
    protected static string $resource = RestrictionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة قيد')];
    }
}
