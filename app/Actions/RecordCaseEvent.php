<?php

namespace App\Actions;

use App\Enums\CaseEventType;
use App\Models\CaseEvent;
use App\Models\Counseling;
use App\Models\Sharing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Pencatat jejak penanganan kasus.
 *
 * Dipanggil eksplisit di setiap titik transisi, bukan lewat observer. Alasannya
 * konkret: empat jalur tulis di rantai kasus memakai mass update pada query
 * builder sehingga tidak memicu model event sama sekali —
 * ProcessNlpAnalysisJob (satu-satunya penulis sharings.priority dan
 * cutdown_for_report), penandaan false-positive, UpdateRelatedSharingPriority,
 * dan ClearCutdown. Perekam berbasis observer akan melewatkan justru bagian
 * yang paling perlu dilacak.
 *
 * Catatan arsitektur: agent/RULE_OF_ARCHITECT.md §6 mewajibkan efek samping
 * lewat Event & Listener. Pengecualian di sini disengaja — alternatifnya ~28
 * kelas event baru untuk satu insert per kejadian, sementara sebagian titik
 * tulisnya tidak bisa dijangkau model event. Empat event domain yang sudah ada
 * dan belum punya listener tetap disambungkan ke action ini.
 */
class RecordCaseEvent
{
    /**
     * @param  array<string, mixed>  $payload  Metadata terstruktur saja — JANGAN
     *                                         pernah memuat transkrip curhat atau
     *                                         catatan klinis.
     */
    public function handle(
        CaseEventType $event,
        Sharing|int|null $sharing = null,
        Counseling|int|null $counseling = null,
        array $payload = [],
        User|int|null $actor = null,
        ?Carbon $occurredAt = null,
    ): ?CaseEvent {
        try {
            $counselingId = $this->idOf($counseling);
            $sharingId    = $this->idOf($sharing) ?? $this->resolveSharingId($counseling);

            if ($sharingId === null && $counselingId === null) {
                // Tidak ada kasus yang bisa dilekati; mencatatnya hanya jadi sampah.
                return null;
            }

            $actorModel = $this->resolveActor($actor, $event);

            return CaseEvent::create([
                'sharing_id'    => $sharingId,
                'counseling_id' => $counselingId,
                'event'         => $event->value,
                'actor_id'      => $actorModel?->id,
                'actor_role'    => $actorModel?->role,
                'occurred_at'   => $occurredAt ?? now(),
                'payload'       => $payload === [] ? null : $payload,
            ]);
        } catch (\Throwable $e) {
            // Perekaman jejak tidak boleh menggagalkan transaksi bisnisnya.
            // Kehilangan satu baris jejak jauh lebih ringan daripada menolak
            // curhat siswa atau membatalkan konfirmasi psikolog.
            Log::error('RecordCaseEvent gagal: '.$e->getMessage(), [
                'event'         => $event->value,
                'sharing_id'    => $this->idOf($sharing),
                'counseling_id' => $this->idOf($counseling),
            ]);

            return null;
        }
    }

    private function idOf(Sharing|Counseling|User|int|null $subject): ?int
    {
        if ($subject === null) {
            return null;
        }

        return is_int($subject) ? $subject : $subject->id;
    }

    /**
     * Curhat adalah head entity, jadi selalu diisi bila bisa diturunkan dari
     * konselingnya — supaya timeline di level curhat tetap utuh.
     */
    private function resolveSharingId(Counseling|int|null $counseling): ?int
    {
        if ($counseling === null) {
            return null;
        }

        if ($counseling instanceof Counseling) {
            return $counseling->sharing_id;
        }

        return Counseling::whereKey($counseling)->value('sharing_id');
    }

    private function resolveActor(User|int|null $actor, CaseEventType $event): ?User
    {
        if ($event->isSystemEvent()) {
            // Dipicu job atau scheduler. Kalaupun ada pengguna yang sedang
            // login, dia bukan pelaku kejadian ini.
            return null;
        }

        if ($actor instanceof User) {
            return $actor;
        }

        if (is_int($actor)) {
            return User::find($actor);
        }

        $authenticated = Auth::user();

        return $authenticated instanceof User ? $authenticated : null;
    }
}
