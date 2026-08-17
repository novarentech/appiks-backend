<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Sharing;
use App\Models\User;
use App\Notifications\RedZoneAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class ZoneNotificationDispatcher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Sharing $incident) {}

    public function handle(): void
    {
        // 1. Get the assigned Guru BK
        $counselor = $this->incident->user->counselor;

        // 2. Get the Principal(s) of the same school
        $schoolId = $this->incident->user->school_id;
        $principals = User::where('role', UserRole::HEADTEACHER->value)
            ->where('school_id', $schoolId)
            ->get();

        $recipients = collect([$counselor])->merge($principals)->filter();

        // 3. Dispatch Notification
        Notification::send($recipients, new RedZoneAlertNotification($this->incident));
    }
}
