<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LookupLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['identity_number', 'user_id', 'center_id', 'result', 'looked_up_at'];

    protected $casts = ['looked_up_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
