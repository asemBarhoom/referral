<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRecord extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'project_id',
        'center_id',
        'staff_id',
        'service_time',
        'service_date',
        'source',
        'match_code',
        'is_exceptional',
        'exception_reason_id',
        'exception_note',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'idempotency_key',
    ];

    protected $casts = [
        'service_time' => 'datetime',
        'service_date' => 'date',
        'is_exceptional' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    // Match codes from spec
    const CODE_INVALID_ID = 'INVALID_ID';
    const CODE_UNKNOWN_ID = 'UNKNOWN_ID';
    const CODE_MERGED_ID = 'MERGED_ID';
    const CODE_BLOCKED = 'BLOCKED';
    const CODE_CLOSED_ENROLLMENT = 'CLOSED_ENROLLMENT';
    const CODE_WRONG_PROJECT = 'WRONG_PROJECT';
    const CODE_WRONG_CENTER = 'WRONG_CENTER';
    const CODE_NO_RECEPTION_VISIT = 'NO_RECEPTION_VISIT';
    const CODE_NOT_REFERRED_TODAY = 'NOT_REFERRED_TODAY';
    const CODE_DUPLICATE_SERVICE = 'DUPLICATE_SERVICE';
    const CODE_PENDING_ENROLLMENT = 'PENDING_ENROLLMENT';
    const CODE_MATCHED = 'MATCHED';

    const REVIEW_PENDING = 'pending';
    const REVIEW_APPROVED = 'approved';
    const REVIEW_REJECTED = 'rejected';

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

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function exceptionReason(): BelongsTo
    {
        return $this->belongsTo(ReasonOption::class, 'exception_reason_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
