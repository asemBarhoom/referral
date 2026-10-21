<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'username', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    const ROLE_RECEPTION = 'reception';
    const ROLE_POINT     = 'point';
    const ROLE_ADMIN     = 'admin';

    const DEMO_DOMAIN = 'demo.test';

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && in_array($this->role, [self::ROLE_RECEPTION, self::ROLE_POINT, self::ROLE_ADMIN], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'user_centers')->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'user_projects')->withTimestamps();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'staff_id');
    }
}
