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
            ->error()
            ->subject("[URGENT] Kasus Red Zone Terdeteksi: {$studentName}")
            ->greeting('Peringatan Insiden Kritis (Red Zone)')
            ->line('Terdeteksi insiden prioritas tinggi yang memerlukan tindakan segera.')
            ->line("**Nama Siswa:** {$studentName}")
            ->line("**Waktu Kejadian:** {$time}")
            ->line('**Batas Waktu Penanganan (SLA):** 2 Jam')
            ->action('Lihat Kasus', $caseUrl)
            ->line('Notifikasi ini bersifat rahasia dan tidak memuat kutipan teks curhat untuk menjaga privasi siswa.');
    }

    public function toArray($notifiable): array
    {
        $studentName = $this->incident->user?->name ?? 'Siswa';
        $time = $this->incident->created_at?->toIso8601String();
        $room = $this->incident->user?->room;
        $studentRoom = $room ? trim(($room->level ?? '') . ' ' . ($room->name ?? '')) : null;
        $counseling = $this->incident->counseling;
        $counselingStatus = $counseling?->status instanceof \BackedEnum
            ? $counseling->status->value
            : ($counseling?->status ? (string) $counseling->status : null);

        return [
            'incident_id'        => $this->incident->id,
            'counseling_id'      => $counseling?->id,
            'counseling_status'  => $counselingStatus,
            'student_name'       => $studentName,
            'student_room'       => $studentRoom,
            'counselor_name'     => $this->incident->user?->counselor?->name,
            'zone'               => 'Red Zone',
            'level'              => 'critical',
            'time'               => $time,
            'url'                => $this->getCaseUrl($notifiable),
            'message'            => "Peringatan Red Zone terdeteksi untuk {$studentName}",
            'created_at'         => $this->incident->created_at,
        ];
    }
}
