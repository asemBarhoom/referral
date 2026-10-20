<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReasonOption extends Model
{
    protected $fillable = ['category', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    // Categories: exception, new_project, center_transfer, cancel_visit
    const CATEGORY_EXCEPTION = 'exception';
    const CATEGORY_NEW_PROJECT = 'new_project';
    const CATEGORY_CENTER_TRANSFER = 'center_transfer';
    const CATEGORY_CANCEL_VISIT = 'cancel_visit';
}
