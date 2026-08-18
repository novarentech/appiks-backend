<?php

namespace App\Actions;

use App\Enums\ConsentStatus;
use App\Jobs\GenerateGeminiReferralSummaryJob;
use App\Models\CounselingConsent;

class UpdateConsentAction
{
    /**
     * Execute the consent status updates.
     *
     * @param CounselingConsent $consent
     * @param bool $isGranted
     * @param array $scopes
     * @return CounselingConsent
     */
    public function execute(CounselingConsent $consent, bool $isGranted, array $scopes = []): CounselingConsent
    {
        if ($isGranted) {
            $consent->status = ConsentStatus::GRANTED;
            $consent->scopes = $scopes;
            $consent->granted_at = now();
            $consent->rejected_at = null;
            $consent->save();

            $counseling = $consent->counseling;
            if ($counseling) {
                GenerateGeminiReferralSummaryJob::dispatch($counseling);
            }
        } else {
            $consent->status = ConsentStatus::REJECTED;
            $consent->rejected_at = now();
            $consent->granted_at = null;
            $consent->scopes = null;
            $consent->save();
        }

        return $consent;
    }
}
