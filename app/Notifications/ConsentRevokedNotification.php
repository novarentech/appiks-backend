<?php

namespace App\Notifications;

use App\Models\Counseling;
use App\Models\CounselingConsent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsentRevokedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Counseling $counseling,
        public CounselingConsent $consent
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $student = $this->counseling->student;
        $studentName = $student?->name ?? 'Siswa';
        $revokedAt = $this->consent->revoked_at
            ? Carbon::parse($this->consent->revoked_at)
            : now();

        $room = $student?->room;
        $studentRoom = $room ? trim(($room->level ?? '') . ' ' . ($room->name ?? '')) : null;

        $counselingStatus = $this->counseling->status instanceof \BackedEnum
            ? $this->counseling->status->value
            : ($this->counseling->status ? (string) $this->counseling->status : 'menunggu_diskusi');

        $timeStr = $revokedAt->toIso8601String();
        $formattedTime = $revokedAt->format('d M Y, H:i');

        return [
            'incident_id'        => $this->counseling->sharing_id,
            'counseling_id'      => $this->counseling->id,
            'counseling_status'  => $counselingStatus,
            'student_id'         => $student?->id,
            'student_name'       => $studentName,
            'student_room'       => $studentRoom,
            'counselor_name'     => $notifiable->name ?? null,
            'revoked_at'         => $timeStr,
            'time'               => $timeStr,
            'formatted_time'     => $formattedTime,
            'type'               => 'consent_revoked',
            'message'            => "{$studentName} telah mencabut persetujuan akses data pada {$formattedTime}. Silakan ajak siswa berdialog.",
            'url'                => "/bk/counselings/{$this->counseling->id}",
            'created_at'         => now()->toIso8601String(),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $student = $this->counseling->student;
        $studentName = $student?->name ?? 'Siswa';
        $revokedAt = $this->consent->revoked_at
            ? Carbon::parse($this->consent->revoked_at)
            : now();
        $formattedTime = $revokedAt->format('d M Y, H:i');

        return (new MailMessage)
            ->subject("[Penting] Pencabutan Persetujuan Akses Data: {$studentName}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Siswa {$studentName} telah mencabut persetujuan akses data pada {$formattedTime}.")
            ->line("Status kasus kini diubah menjadi 'Menunggu Diskusi' agar Anda dapat mengajak siswa berdialog alih-alih membiarkan rujukannya terhenti.")
            ->action('Lihat Kasus', url("/bk/counselings/{$this->counseling->id}"));
    }
}
