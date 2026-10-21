<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Center extends Model
{
    protected $fillable = ['code', 'name', 'color', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'center_projects')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_centers')->withTimestamps();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
