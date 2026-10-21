<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProject;
use App\Models\IdentityType;
use App\Models\ReceptionVisit;
use App\Models\VisitReferral;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReceptionService
{
    /**
     * Register (or append to) a reception visit for an existing beneficiary.
     * If a valid visit exists today at this center, returns it with new referrals added.
     */
    public static function registerVisit(
        Beneficiary $beneficiary,
        int $centerId,
        int $staffId,
        array $projectIds
    ): ReceptionVisit {
        return DB::transaction(function () use ($beneficiary, $centerId, $staffId, $projectIds) {
            $today = Carbon::now('Asia/Gaza');
            $todayStr = $today->toDateString();

            // Re-use existing valid visit today at same center
            $visit = ReceptionVisit::where('beneficiary_id', $beneficiary->id)
                ->where('center_id', $centerId)
                ->where('visit_date', $todayStr)
                ->where('status', ReceptionVisit::STATUS_VALID)
                ->lockForUpdate()
                ->first();

            if (!$visit) {
                $isNew = !BeneficiaryProject::where('beneficiary_id', $beneficiary->id)
                    ->where('center_id', $centerId)
                    ->exists();

                $visitNumber = ReceptionVisit::where('center_id', $centerId)
                    ->where('visit_date', $todayStr)
                    ->where('status', ReceptionVisit::STATUS_VALID)
                    ->count() + 1;

                $visit = ReceptionVisit::create([
                    'beneficiary_id'  => $beneficiary->id,
                    'visit_number'    => $visitNumber,
                    'type'            => $isNew ? ReceptionVisit::TYPE_NEW : ReceptionVisit::TYPE_FOLLOWUP,
                    'center_id'       => $centerId,
                    'staff_id'        => $staffId,
                    'visit_time'      => $today,
                    'visit_date'      => $todayStr,
                    'status'          => ReceptionVisit::STATUS_VALID,
                    'idempotency_key' => Str::uuid(),
                ]);
            }

            foreach ($projectIds as $projectId) {
                $enrollment = BeneficiaryProject::where('beneficiary_id', $beneficiary->id)
                    ->where('project_id', $projectId)
                    ->where('center_id', $centerId)
                    ->first();

                if (!$enrollment) {
                    $enrollment = BeneficiaryProject::create([
                        'beneficiary_id' => $beneficiary->id,
                        'project_id'     => $projectId,
                        'center_id'      => $centerId,
                        'status'         => BeneficiaryProject::STATUS_PENDING,
                    ]);
                }

                $alreadyReferred = VisitReferral::where('visit_id', $visit->id)
                    ->where('beneficiary_project_id', $enrollment->id)
                    ->exists();

                if (!$alreadyReferred) {
                    $projectVisitNum = VisitReferral::whereHas('visit', fn($q) =>
                        $q->where('center_id', $centerId)->where('visit_date', $todayStr)
                    )->whereHas('beneficiaryProject', fn($q) =>
                        $q->where('project_id', $projectId)
                    )->count() + 1;

                    VisitReferral::create([
                        'visit_id'               => $visit->id,
                        'beneficiary_project_id' => $enrollment->id,
                        'project_visit_number'   => $projectVisitNum,
                    ]);
                }
            }

            return $visit;
        });
    }

    /**
     * Create a new beneficiary and register their first visit.
     */
    public static function createAndRegister(
        string $identityNumber,
        int $identityTypeId,
        string $name,
        string $phone,
        ?string $dob,
        int $centerId,
        int $staffId,
        array $projectIds
    ): Beneficiary {
        return DB::transaction(function () use ($identityNumber, $identityTypeId, $name, $phone, $dob, $centerId, $staffId, $projectIds) {
            $beneficiary = Beneficiary::create([
                'identity_number'       => $identityNumber,
                'identity_type_id'      => $identityTypeId,
                'name'                  => $name,
                'phone'                 => $phone,
                'dob'                   => $dob,
                'registration_center_id' => $centerId,
                'status'                => Beneficiary::STATUS_ACTIVE,
            ]);

            self::registerVisit($beneficiary, $centerId, $staffId, $projectIds);

            return $beneficiary;
        });
    }
}
