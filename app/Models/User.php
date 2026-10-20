<?php

namespace App\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasPanelShield;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Role constants matching spec
    const ROLE_RECEPTION = 'reception';
    const ROLE_PROJECT_STAFF = 'project_staff';
    const ROLE_CENTER_ADMIN = 'center_admin';
    const ROLE_DASHBOARD_ADMIN = 'dashboard_admin';

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'user_centers')->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'user_projects')
            ->withPivot('center_id')
            ->withTimestamps();
    }
}
