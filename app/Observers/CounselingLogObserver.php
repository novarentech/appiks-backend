<?php

namespace App\Observers;

use App\Models\CounselingLog;
use App\Models\CounselingLogHistory;
use Illuminate\Support\Facades\Auth;

class CounselingLogObserver
{
    /**
     * Handle the CounselingLog "updating" event.
     */
    public function updating(CounselingLog $counselingLog): void
    {
        if ($counselingLog->isDirty('clinical_notes')) {
            CounselingLogHistory::create([
                'counseling_log_id' => $counselingLog->id,
                'clinical_notes' => $counselingLog->getOriginal('clinical_notes'),
                'updated_by' => Auth::id() ?? $counselingLog->counselor_id,
            ]);
        }
    }
}
