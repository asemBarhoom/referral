<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entry extends Model
{
    const TYPE_RECEPTION = 'reception';
    const TYPE_POINT     = 'point';

    protected $fillable = [
        'beneficiary_id',
        'center_id',
        'staff_id',
        'type',
        'is_new',
        'entry_time',
        'entry_date',
        'idempotency_key',
    ];

    protected $casts = [
        'is_new' => 'boolean',
        'entry_time' => 'datetime',
        'entry_date' => 'date',
    ];

    // Store as plain Y-m-d so where('entry_date', 'Y-m-d') matches on SQLite too.
    protected function entryDate(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'entry_projects')->withTimestamps();
    }

    public function entryProjects(): HasMany
    {
        return $this->hasMany(EntryProject::class);
    }
}
