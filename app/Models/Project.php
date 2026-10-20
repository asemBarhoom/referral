<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['code', 'name', 'allow_exceptional_service', 'is_active'];

    protected $casts = [
        'allow_exceptional_service' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_projects')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function beneficiaryProjects(): HasMany
    {
        return $this->hasMany(BeneficiaryProject::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }
}
