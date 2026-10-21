<?php

namespace App\Filament\Resources\EntryResource\Pages;

use App\Filament\Resources\EntryResource;
use App\Models\ChangeLog;
use App\Models\Entry;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditEntry extends EditRecord
{
    protected static string $resource = EntryResource::class;

    private array $oldProjectIds = [];

    protected function beforeSave(): void
    {
        $this->oldProjectIds = $this->currentProjectIds();
    }

    protected function afterSave(): void
    {
        $newProjectIds = $this->currentProjectIds();

        if ($newProjectIds === $this->oldProjectIds) {
            return;
        }

        ChangeLog::create([
            'user_id'      => auth()->id(),
            'action'       => 'updated_entry_projects',
            'subject_type' => Entry::class,
            'subject_id'   => $this->record->id,
            'old_value'    => ['project_ids' => $this->oldProjectIds],
            'new_value'    => ['project_ids' => $newProjectIds],
            'changed_at'   => Carbon::now('Asia/Gaza'),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    private function currentProjectIds(): array
    {
        return $this->record->projects()->orderBy('projects.id')->pluck('projects.id')->all();
    }
}
