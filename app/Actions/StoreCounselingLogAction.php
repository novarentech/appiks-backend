<?php

namespace App\Actions;

use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
use App\Events\CounselingLogStored;
use App\Models\Counseling;
use App\Models\CounselingLog;
use Illuminate\Support\Facades\Auth;

class StoreCounselingLogAction
{
    /**
     * Execute the action to store the counseling log and complete the session.
     */
    public function handle(array $validated): CounselingLog
    {
        $counseling = Counseling::findOrFail($validated['counseling_id']);

        // Create the counseling log
        $counselingLog = CounselingLog::create([
            'counseling_id' => $counseling->id,
            'student_id' => $counseling->student_id,
            'counselor_id' => Auth::id(),
            'session_mode' => $validated['session_mode'],
            'clinical_notes' => $validated['clinical_notes'],
            'resolution_status' => $validated['resolution_status'],
        ]);

        // Update the parent Counseling session
        $counseling->update([
            'status' => CounselingStatus::SELESAI->value,
            'resolution' => $validated['resolution_status'],
            'method' => $validated['session_mode'],
        ]);

        // If this counseling session was linked to a high-risk report, close the report too
        if ($counseling->report_id) {
            $counseling->report->update([
                'status' => ReportStatus::SELESAI->value,
                'result' => 'Konseling selesai dengan resolusi: ' . $validated['resolution_status'],
            ]);
        }

        // Dispatch event for decoupled side effects
        CounselingLogStored::dispatch($counselingLog);

        return $counselingLog;
    }
}
