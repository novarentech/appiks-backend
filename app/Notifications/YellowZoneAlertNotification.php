<?php

namespace App\Notifications;

use App\Models\Sharing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class YellowZoneAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Sharing $incident) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    protected function getCaseUrl($notifiable): string
    {
        $base = rtrim(config('app.frontend_url', url('/')), '/');
        if (($notifiable->role ?? null) === 'headteacher') {
            return "{$base}/headteacher/dashboard?incident_id={$this->incident->id}";
        }

        return "{$base}/bk/incidents?incident_id={$this->incident->id}";
    }

    public function toMail($notifiable): MailMessage
    {
        $studentName = $this->incident->user?->name ?? 'Siswa';
        $time = $this->incident->created_at?->format('d M Y, H:i') ?? $this->incident->created_at?->toIso8601String();
        $caseUrl = $this->getCaseUrl($notifiable);

        return (new MailMessage)
            ->subject("[PERHATIAN] Kasus Yellow Zone Terdeteksi: {$studentName}")
            ->greeting('Pemberitahuan Insiden Perhatian (Yellow Zone)')
            ->line('Terdeteksi insiden tingkat perhatian (Yellow Zone) yang memerlukan pemantauan dan tindak lanjut.')
            ->line("**Nama Siswa:** {$studentName}")
            ->line("**Waktu Kejadian:** {$time}")
            ->line('**Batas Waktu Penanganan (SLA):** 72 Jam')
            ->action('Lihat Kasus', $caseUrl)
            ->line('Notifikasi ini bersifat rahasia dan tidak memuat kutipan teks curhat untuk menjaga privasi siswa.');
    }

    public function toArray($notifiable): array
    {
        $studentName = $this->incident->user?->name ?? 'Siswa';
        $time = $this->incident->created_at?->toIso8601String();

        return [
            'incident_id'  => $this->incident->id,
            'student_name' => $studentName,
            'student_room' => $this->incident->user?->room->level . ' ' . $this->incident->user?->room->name,
            'counselor_name' => $this->incident->user?->counselor->name,
            'zone'         => 'Yellow Zone',
            'level'        => 'warning',
            'time'         => $time,
            'url'          => $this->getCaseUrl($notifiable),
            'message'      => "Pemberitahuan Yellow Zone terdeteksi untuk {$studentName}",
            'created_at'   => $this->incident->created_at,
        ];
    }
}
