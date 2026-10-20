<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeneficiaryProject extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'project_id',
        'center_id',
        'status',
        'reason',
        'approved_by',
        'closed_at',
        'close_reason',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending_approval';
    const STATUS_CLOSED = 'closed';

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function visitReferrals(): HasMany
    {
        return $this->hasMany(VisitReferral::class);
    }
}
