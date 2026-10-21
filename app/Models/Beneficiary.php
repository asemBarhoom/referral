<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficiary extends Model
{
    protected $fillable = [
        'identity_number',
        'name',
        'first_entry_date',
    ];

    protected $casts = [
        'first_entry_date' => 'date',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(ChangeLog::class, 'subject_id')
            ->where('subject_type', self::class);
    }
}
