<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryProject extends Model
{
    protected $fillable = ['entry_id', 'project_id'];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
