<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceptionVisit extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'visit_number',
        'type',
        'center_id',
        'device_id',
        'staff_id',
        'visit_time',
        'visit_date',
        'status',
        'cancel_reason',
        'cancelled_by',
        'idempotency_key',
    ];

    protected $casts = [
        'visit_time' => 'datetime',
        'visit_date' => 'date',
    ];

    const TYPE_NEW = 'new';
    const TYPE_FOLLOWUP = 'followup';
    const STATUS_VALID = 'valid';
    const STATUS_CANCELLED = 'cancelled';

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(VisitReferral::class, 'visit_id');
    }
}
