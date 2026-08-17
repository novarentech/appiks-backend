<?php

namespace App\Notifications;

use App\Models\Sharing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RedZoneAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Sharing $incident) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('URGENT: Red Zone Incident Detected')
            ->line('A high-priority incident requires immediate attention.')
            ->action('View Dashboard', url('/headteacher/dashboard'))
            ->line('Please ensure the assigned Guru BK is handling the situation.');
    }

    public function toArray($notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'message'     => 'Red Zone Incident Detected',
            'created_at'  => $this->incident->created_at,
        ];
    }
}
