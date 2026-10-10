<?php

namespace App\Listeners;

use App\Actions\RecordCaseEvent;
use App\Enums\CaseEventType;
use App\Events\ReportCreated;
use App\Models\Sharing;

class UpdateRelatedSharingPriority
{
    public function __construct(private RecordCaseEvent $recordEvent) {}

    public function handle(ReportCreated $event): void
    {
        $affected = Sharing::where('user_id', $event->report->user_id)
            ->whereDate('created_at', now()->toDateString())
            ->get();

        foreach ($affected as $sharing) {
            if ($sharing->priority === 'tinggi') {
                continue;
            }

            $this->recordEvent->handle(CaseEventType::PRIORITY_ESCALATED, $sharing, payload: [
                'from'    => $sharing->priority,
                'to'      => 'tinggi',
                'trigger' => 'report_created',
            ]);
        }

        Sharing::whereIn('id', $affected->pluck('id'))->update(['priority' => 'tinggi']);
    }
}
