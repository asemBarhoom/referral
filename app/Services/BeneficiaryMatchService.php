<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProject;
use App\Models\LookupLog;
use App\Models\ReceptionVisit;
use App\Models\Restriction;
use App\Models\ServiceRecord;
use App\Models\VisitReferral;
use Carbon\Carbon;

class BeneficiaryMatchService
{
    /** Normalize raw identity input: Arabic/Persian digits → ASCII, strip non-digits */
    public static function normalize(string $raw): string
    {
        $map = [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ];
        return preg_replace('/\D/', '', strtr($raw, $map));
    }

    public static function isValid(string $normalized): bool
    {
        return strlen($normalized) === 9 && ctype_digit($normalized);
    }

    /** Palestinian national ID checksum (Luhn-like over 9 digits) */
    public static function validateChecksum(string $id): bool
    {
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $d = (int) $id[$i];
            if ($i % 2 !== 0) {
                $d *= 2;
                if ($d >= 10) {
                    $d = intdiv($d, 10) + ($d % 10);
                }
            }
            $sum += $d;
        }
        return $sum % 10 === 0;
    }

    /** Log a lookup event */
    public static function log(string $normalizedId, int $userId, ?int $centerId, string $result): void
    {
        LookupLog::create([
            'identity_number' => $normalizedId,
            'user_id'        => $userId,
            'center_id'      => $centerId,
            'result'         => $result,
            'looked_up_at'   => now(),
        ]);
    }

    /**
     * Look up a beneficiary for the reception desk.
     * Returns: code (INVALID_ID|UNKNOWN_ID|MERGED_ID|FOUND), beneficiary, restrictions, today_visit
     */
    public static function lookupForReception(string $rawId, int $centerId): array
    {
        $id = self::normalize($rawId);

        if (!self::isValid($id)) {
            return ['code' => 'INVALID_ID', 'beneficiary' => null, 'identity_number' => $id];
        }

        $beneficiary = Beneficiary::with([
            'identityType',
            'registrationCenter',
            'activeRestrictions.reasonOption',
            'projects' => fn($q) => $q->with('project')->where('center_id', $centerId),
        ])->where('identity_number', $id)->first();

        if (!$beneficiary) {
            return ['code' => 'UNKNOWN_ID', 'beneficiary' => null, 'identity_number' => $id];
        }

        if ($beneficiary->status === Beneficiary::STATUS_MERGED) {
            return ['code' => 'MERGED_ID', 'beneficiary' => $beneficiary, 'identity_number' => $id];
        }

        $today     = Carbon::now('Asia/Gaza')->toDateString();
        $todayVisit = ReceptionVisit::where('beneficiary_id', $beneficiary->id)
            ->where('center_id', $centerId)
            ->where('visit_date', $today)
            ->where('status', ReceptionVisit::STATUS_VALID)
            ->with('referrals.beneficiaryProject.project')
            ->first();

        $block = $beneficiary->activeRestrictions
            ->whereIn('level', [Restriction::LEVEL_TEMPORARY_BLOCK, Restriction::LEVEL_PERMANENT_BLOCK])
            ->first();

        return [
            'code'           => 'FOUND',
            'beneficiary'    => $beneficiary,
            'identity_number' => $id,
            'block'          => $block,
            'today_visit'    => $todayVisit,
        ];
    }

    /**
     * Match a beneficiary for project-point service confirmation.
     * Returns: code, beneficiary, enrollment, visit, referral
     */
    public static function matchForService(string $rawId, int $projectId, int $centerId): array
    {
        $id = self::normalize($rawId);

        // 1. INVALID_ID
        if (!self::isValid($id)) {
            return ['code' => ServiceRecord::CODE_INVALID_ID, 'beneficiary' => null, 'identity_number' => $id];
        }

        $today = Carbon::now('Asia/Gaza')->toDateString();

        // 2. UNKNOWN_ID
        $beneficiary = Beneficiary::where('identity_number', $id)->first();
        if (!$beneficiary) {
            return ['code' => ServiceRecord::CODE_UNKNOWN_ID, 'beneficiary' => null, 'identity_number' => $id];
        }

        // 3. MERGED_ID
        if ($beneficiary->status === Beneficiary::STATUS_MERGED) {
            return ['code' => ServiceRecord::CODE_MERGED_ID, 'beneficiary' => $beneficiary];
        }

        // 4. BLOCKED
        $block = $beneficiary->activeRestrictions()
            ->whereIn('level', [Restriction::LEVEL_TEMPORARY_BLOCK, Restriction::LEVEL_PERMANENT_BLOCK])
            ->first();
        if ($block) {
            return ['code' => ServiceRecord::CODE_BLOCKED, 'beneficiary' => $beneficiary, 'restriction' => $block];
        }

        // Enrollment lookup
        $enrollment = BeneficiaryProject::where('beneficiary_id', $beneficiary->id)
            ->where('project_id', $projectId)
            ->first();

        // 5. CLOSED_ENROLLMENT
        if ($enrollment && $enrollment->status === BeneficiaryProject::STATUS_CLOSED) {
            return ['code' => ServiceRecord::CODE_CLOSED_ENROLLMENT, 'beneficiary' => $beneficiary, 'enrollment' => $enrollment];
        }

        // 6. WRONG_PROJECT (no enrollment for this project at any center)
        if (!$enrollment) {
            return ['code' => ServiceRecord::CODE_WRONG_PROJECT, 'beneficiary' => $beneficiary];
        }

        // 7. WRONG_CENTER
        if ($enrollment->center_id !== $centerId) {
            return ['code' => ServiceRecord::CODE_WRONG_CENTER, 'beneficiary' => $beneficiary, 'enrollment' => $enrollment];
        }

        // 8. NO_RECEPTION_VISIT
        $visit = ReceptionVisit::where('beneficiary_id', $beneficiary->id)
            ->where('center_id', $centerId)
            ->where('visit_date', $today)
            ->where('status', ReceptionVisit::STATUS_VALID)
            ->first();
        if (!$visit) {
            return ['code' => ServiceRecord::CODE_NO_RECEPTION_VISIT, 'beneficiary' => $beneficiary, 'enrollment' => $enrollment];
        }

        // 10. DUPLICATE_SERVICE (check before NOT_REFERRED_TODAY per spec ordering intent)
        $duplicate = ServiceRecord::where('beneficiary_id', $beneficiary->id)
            ->where('project_id', $projectId)
            ->where('service_date', $today)
            ->where('review_status', '!=', ServiceRecord::REVIEW_REJECTED)
            ->first();
        if ($duplicate) {
            return ['code' => ServiceRecord::CODE_DUPLICATE_SERVICE, 'beneficiary' => $beneficiary, 'service' => $duplicate];
        }

        // 9. NOT_REFERRED_TODAY
        $referral = VisitReferral::where('visit_id', $visit->id)
            ->where('beneficiary_project_id', $enrollment->id)
            ->first();
        if (!$referral) {
            return ['code' => ServiceRecord::CODE_NOT_REFERRED_TODAY, 'beneficiary' => $beneficiary, 'enrollment' => $enrollment, 'visit' => $visit];
        }

        // 11. PENDING_ENROLLMENT
        if ($enrollment->status === BeneficiaryProject::STATUS_PENDING) {
            return ['code' => ServiceRecord::CODE_PENDING_ENROLLMENT, 'beneficiary' => $beneficiary, 'enrollment' => $enrollment, 'visit' => $visit, 'referral' => $referral];
        }

        // 12. MATCHED
        return ['code' => ServiceRecord::CODE_MATCHED, 'beneficiary' => $beneficiary, 'enrollment' => $enrollment, 'visit' => $visit, 'referral' => $referral];
    }
}
