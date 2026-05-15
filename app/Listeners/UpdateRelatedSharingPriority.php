<?php

namespace App\Listeners;

use App\Events\ReportCreated;
use App\Models\Sharing;

class UpdateRelatedSharingPriority
{
    public function handle(ReportCreated $event): void
    {
        Sharing::where('user_id', $event->report->user_id)
            ->whereDate('created_at', now()->toDateString())
            ->update(['priority' => 'tinggi']);
    }
}
