<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitReferral extends Model
{
    protected $fillable = ['visit_id', 'beneficiary_project_id', 'project_visit_number'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(ReceptionVisit::class, 'visit_id');
    }

    public function beneficiaryProject(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryProject::class);
    }
}
