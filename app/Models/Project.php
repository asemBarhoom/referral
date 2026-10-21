<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['code', 'name', 'display_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_projects')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_projects')->withTimestamps();
    }

    public function entryProjects(): HasMany
    {
        return $this->hasMany(EntryProject::class);
    }
}
