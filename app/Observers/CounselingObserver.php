<?php

namespace App\Observers;

use App\Actions\RecordCaseEvent;
use App\Enums\CaseEventType;
use App\Enums\ConsentStatus;
use App\Enums\ReportStatus;
use App\Models\Counseling;

class CounselingObserver
{
    /**
     * Handle the Counseling "created" event.
     *
     * @param Counseling $counseling
     * @return void
     */
    public function created(Counseling $counseling): void
    {
        // Automatically create a pending consent request if this is an external counseling session/referral
        $recordEvent = app(RecordCaseEvent::class);

        $recordEvent->handle(CaseEventType::COUNSELING_CREATED, $counseling->sharing_id, $counseling, payload: [
            'type'        => $counseling->type,
            'source_type' => $counseling->source_type,
        ]);

        if ($counseling->type === 'external') {
            $counseling->consents()->create([
                'status' => ConsentStatus::PENDING,
            ]);

            $recordEvent->handle(CaseEventType::CONSENT_REQUESTED, $counseling->sharing_id, $counseling);
        }
        if($counseling->sharing_id != null){
            $counseling->sharing->update([
                'status'=> ReportStatus::MENUNGGU_PERSETUJUAN->value
            ]);
        }
    }
}
