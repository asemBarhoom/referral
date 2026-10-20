<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficiary extends Model
{
    protected $fillable = [
        'identity_number',
        'identity_type_id',
        'name',
        'name_normalized',
        'phone',
        'dob',
        'registration_center_id',
        'family_id',
        'status',
        'merged_into_id',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_MERGED = 'merged';

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->isDirty('name')) {
                $model->name_normalized = self::normalize($model->name);
            }
        });
    }

    public static function normalize(string $name): string
    {
        // Normalize Arabic text for search: strip diacritics, normalize hamza, etc.
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    public function identityType(): BelongsTo
    {
        return $this->belongsTo(IdentityType::class);
    }

    public function registrationCenter(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'registration_center_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class, 'family_id');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(Beneficiary::class, 'family_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class, 'merged_into_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(BeneficiaryProject::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ReceptionVisit::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function restrictions(): HasMany
    {
        return $this->hasMany(Restriction::class);
    }

    public function activeRestrictions(): HasMany
    {
        return $this->restrictions()->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}
