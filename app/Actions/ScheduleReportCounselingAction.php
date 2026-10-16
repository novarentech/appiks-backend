<?php

namespace App\Actions;

use App\Enums\CaseEventType;
use App\Enums\CounselingStatus;
use App\Events\CounselingScheduled;
use App\Models\Counseling;
use Illuminate\Support\Facades\Auth;

class ScheduleReportCounselingAction
{
    /**
     * Execute the action to schedule a counseling session from a report.
     */
    public function handle(Counseling $report, array $validated): Counseling
    {
        $scheduledAt = $validated['proposed_date'] . ' ' . $validated['proposed_time'];

        $report->update([
            'counselor_id' => Auth::id() ?? $report->counselor_id,
            'room' => $validated['room'] ?? $report->room,
            'notes' => $validated['notes'] ?? $report->notes,
            'status' => CounselingStatus::MENUNGGU->value,
            'scheduled_at' => $scheduledAt,
        ]);

        app(RecordCaseEvent::class)->handle(
            CaseEventType::COUNSELING_SCHEDULE_PROPOSED,
            $report->sharing_id,
            $report,
            payload: ['scheduled_at' => $scheduledAt],
        );

        // Dispatch event to handle decoupled side-effects
        CounselingScheduled::dispatch($report);

        return $report;
    }
}
