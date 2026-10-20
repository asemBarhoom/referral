<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = ['center_id', 'name', 'device_token', 'last_seen_at', 'is_disabled'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_disabled' => 'boolean',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
