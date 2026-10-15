<?php

namespace App\Actions\Notification;

use App\Models\Counseling;
use Illuminate\Notifications\DatabaseNotification;

class SyncSharingCounselingStatusAction
{
    /**
     * Synchronize counseling status in all database notifications related to the sharing.
     */
    public function handle(Counseling $counseling): void
    {
        if (! $counseling->sharing_id) {
            return;
        }

        $counselingStatus = $counseling->trashed()
            ? null
            : ($counseling->status?->value ?? (string) $counseling->status);

        $counselingId = $counseling->trashed() ? null : $counseling->id;

        $notifications = DatabaseNotification::where('data->incident_id', $counseling->sharing_id)->get();

        foreach ($notifications as $notification) {
            $data = $notification->data;
            $data['counseling_id'] = $counselingId;
            $data['counseling_status'] = $counselingStatus;
            $notification->data = $data;
            $notification->save();
        }
    }
}
