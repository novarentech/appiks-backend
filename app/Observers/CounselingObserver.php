<?php

namespace App\Observers;

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
        if ($counseling->type === 'external') {
            $counseling->consents()->create([
                'status' => ConsentStatus::PENDING,
            ]);
        }
        if($counseling->sharing_id != null){
            $counseling->sharing->update([
                'status'=> ReportStatus::MENUNGGU_PERSETUJUAN->value
            ]);
        }
    }
}
