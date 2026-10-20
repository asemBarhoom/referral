<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Restriction extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'level',
        'reason',
        'reason_option_id',
        'created_by',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    const LEVEL_WARNING = 'warning';
    const LEVEL_UNDER_REVIEW = 'under_review';
    const LEVEL_TEMPORARY_BLOCK = 'temporary_block';
    const LEVEL_PERMANENT_BLOCK = 'permanent_block';

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function reasonOption(): BelongsTo
    {
        return $this->belongsTo(ReasonOption::class, 'reason_option_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
