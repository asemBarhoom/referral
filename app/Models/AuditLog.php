<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'user_id',
        'center_id',
        'subject_type',
        'subject_id',
        'before',
        'after',
        'ip_address',
        'occurred_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'occurred_at' => 'datetime',
    ];

    // Immutable — never update or delete
    public static function boot(): void
    {
        parent::boot();
        static::updating(fn() => false);
        static::deleting(fn() => false);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
