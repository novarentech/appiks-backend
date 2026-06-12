<?php

namespace App\Actions;

use App\Enums\CounselingStatus;
use App\Events\CounselingScheduled;
use App\Models\Counseling;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ScheduleReportCounselingAction
{
    /**
     * Execute the action to schedule a counseling session from a report.
     */
    public function handle(Report $report, array $validated): Counseling
    {
        $scheduledAt = $validated['proposed_date'] . ' ' . $validated['proposed_time'];

        $counseling = Counseling::create([
            'student_id' => $report->user_id,
            'counselor_id' => Auth::id(),
            'room' => $validated['room'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'reason' => 'Konseling rujukan laporan insiden #' . $report->id,
            'type' => 'internal',
            'source_type' => 'nlp_incident',
            'report_id' => $report->id,
            'status' => CounselingStatus::MENUNGGU->value,
            'scheduled_at' => $scheduledAt,
        ]);

        // Dispatch event to handle decoupled side-effects
        CounselingScheduled::dispatch($counseling);

        return $counseling;
    }
}
