<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Sharing;
use App\Models\User;
use App\Notifications\RedZoneAlertNotification;
use App\Notifications\YellowZoneAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class ZoneNotificationDispatcher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Sharing $incident,
        public string $zone = 'Red Zone'
    ) {}

    public function handle(): void
    {
        $this->incident->loadMissing('user.counselor');
        $schoolId = $this->incident->user?->school_id;

        // 1. Get the assigned Guru BK (or fallback to all counselors in the same school)
        $counselor = $this->incident->user?->counselor;
        $counselors = $counselor
            ? collect([$counselor])
            : User::where('role', UserRole::COUNSELOR->value)->where('school_id', $schoolId)->get();

        // 2. Get the Principal(s) of the same school
        $principals = User::where('role', UserRole::HEADTEACHER->value)
            ->where('school_id', $schoolId)
            ->get();

        $recipients = $counselors->merge($principals)->filter()->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        // 3. Dispatch Notification based on detected zone
        if ($this->zone === 'Yellow Zone') {
            Notification::send($recipients, new YellowZoneAlertNotification($this->incident));
        } else {
            Notification::send($recipients, new RedZoneAlertNotification($this->incident));
        }
    }
}
