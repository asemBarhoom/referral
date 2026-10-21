<?php

namespace App\Filament\Resources\ReasonOptionResource\Pages;

use App\Filament\Resources\ReasonOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReasonOptions extends ListRecords
{
    protected static string $resource = ReasonOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة سبب')];
    }
}
