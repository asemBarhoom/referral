<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReviewItem extends Model
{
    protected $fillable = [
        'type',
        'priority',
        'source_type',
        'source_id',
        'center_id',
        'beneficiary_id',
        'decision',
        'decision_maker_id',
        'decided_at',
        'notes',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_maker_id');
    }
}
