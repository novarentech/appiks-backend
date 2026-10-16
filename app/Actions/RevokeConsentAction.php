<?php

namespace App\Actions;

use App\Enums\CaseEventType;
use App\Enums\ConsentStatus;
use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\CounselingConsent;
use App\Models\User;
use App\Notifications\ConsentRevokedNotification;
use Illuminate\Support\Facades\DB;

class RevokeConsentAction
{
    public function __construct(
        protected RecordCaseEvent $recordEvent
    ) {}

    /**
     * Execute consent revocation by a student.
     */
    public function execute(CounselingConsent $consent): CounselingConsent
    {
        return DB::transaction(function () use ($consent) {
            $revokedAt = now();

            $consent->update([
                'status'     => ConsentStatus::REVOKED,
                'revoked_at' => $revokedAt,
            ]);

            $counseling = $consent->counseling;

            if ($counseling && ! $counseling->status->isTerminal()) {
                $counseling->update([
                    'status' => CounselingStatus::MENUNGGU_DISKUSI->value,
                ]);

                if ($counseling->sharing) {
                    $counseling->sharing->update([
                        'status' => ReportStatus::MENUNGGU_DISKUSI->value,
                    ]);
                }
            }

            // Record case event in audit trail
            $this->recordEvent->handle(
                CaseEventType::CONSENT_REVOKED,
                $counseling?->sharing_id,
                $counseling,
                payload: [
                    'revoked_at'   => $revokedAt->toIso8601String(),
                    'student_name' => $counseling?->student?->name,
                ]
            );

            // Notify handling counselor
            $counselor = $counseling?->counselor ?? $counseling?->student?->counselor;
            if (! $counselor && $counseling?->student?->school_id) {
                $counselor = User::where('school_id', $counseling->student->school_id)
                    ->where('role', UserRole::COUNSELOR->value)
                    ->first();
            }

            if ($counselor) {
                $counselor->notify(new ConsentRevokedNotification($counseling, $consent));
            }

            return $consent->refresh();
        });
    }
}
