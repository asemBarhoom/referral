<?php

namespace App\Services;

use App\Models\ReasonOption;
use App\Models\ReviewItem;
use App\Models\ServiceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectServiceConfirm
{
    /**
     * Confirm a regular service after a MATCHED or PENDING_ENROLLMENT result.
     */
    public static function confirm(array $matchResult, int $projectId, int $centerId, int $staffId): ServiceRecord
    {
        return DB::transaction(function () use ($matchResult, $projectId, $centerId, $staffId) {
            $today = Carbon::now('Asia/Gaza');
            $code  = $matchResult['code'];
            $bene  = $matchResult['beneficiary'];

            $reviewStatus = ($code === ServiceRecord::CODE_MATCHED)
                ? ServiceRecord::REVIEW_APPROVED
                : ServiceRecord::REVIEW_PENDING;

            $record = ServiceRecord::create([
                'beneficiary_id'  => $bene->id,
                'project_id'      => $projectId,
                'center_id'       => $centerId,
                'staff_id'        => $staffId,
                'service_time'    => $today,
                'service_date'    => $today->toDateString(),
                'source'          => 'project_point',
                'match_code'      => $code,
                'is_exceptional'  => false,
                'review_status'   => $reviewStatus,
                'idempotency_key' => Str::uuid(),
            ]);

            if ($reviewStatus === ServiceRecord::REVIEW_PENDING) {
                ReviewItem::create([
                    'type'            => 'pending_enrollment_service',
                    'priority'        => 'normal',
                    'source_type'     => ServiceRecord::class,
                    'source_id'       => $record->id,
                    'center_id'       => $centerId,
                    'beneficiary_id'  => $bene->id,
                ]);
            }

            return $record;
        });
    }

    /**
     * Confirm an exceptional service (requires project to allow_exceptional_service).
     */
    public static function confirmExceptional(
        array $matchResult,
        int $projectId,
        int $centerId,
        int $staffId,
        int $exceptionReasonId,
        string $exceptionNote
    ): ServiceRecord {
        return DB::transaction(function () use ($matchResult, $projectId, $centerId, $staffId, $exceptionReasonId, $exceptionNote) {
            $today = Carbon::now('Asia/Gaza');
            $code  = $matchResult['code'];
            $bene  = $matchResult['beneficiary'];

            $priority = in_array($code, [ServiceRecord::CODE_WRONG_PROJECT])
                ? 'high'
                : 'normal';

            $record = ServiceRecord::create([
                'beneficiary_id'     => $bene->id,
                'project_id'         => $projectId,
                'center_id'          => $centerId,
                'staff_id'           => $staffId,
                'service_time'       => $today,
                'service_date'       => $today->toDateString(),
                'source'             => 'project_point',
                'match_code'         => $code,
                'is_exceptional'     => true,
                'exception_reason_id' => $exceptionReasonId,
                'exception_note'     => $exceptionNote,
                'review_status'      => ServiceRecord::REVIEW_PENDING,
                'idempotency_key'    => Str::uuid(),
            ]);

            ReviewItem::create([
                'type'           => 'exceptional_service',
                'priority'       => $priority,
                'source_type'    => ServiceRecord::class,
                'source_id'      => $record->id,
                'center_id'      => $centerId,
                'beneficiary_id' => $bene->id,
            ]);

            return $record;
        });
    }
}
