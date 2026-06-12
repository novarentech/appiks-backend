<?php

namespace App\Actions;

use App\Enums\ConsentStatus;
use App\Jobs\GenerateAISummaryJob;
use App\Models\CounselingConsent;
use App\Services\HeadlessDataGenerator;

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

            // Run Headless PII reduction generator
            $counseling = $consent->counseling;
            if ($counseling && $counseling->student) {
                $generator = new HeadlessDataGenerator();
                $sanitizedText = $generator->generateSanitizedText($counseling->student);

                // Dispatch Background AI clinical summarization job
                GenerateAISummaryJob::dispatch($consent->counseling_id, $sanitizedText);
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
