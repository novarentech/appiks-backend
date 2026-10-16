<?php

namespace App\Actions;

use App\Enums\CaseEventType;
use App\Enums\ConsentStatus;
use App\Enums\CounselingStatus;
use App\Models\CounselingConsent;

class UpdateConsentAction
{
    /**
     * Execute the consent status updates.
     *
     * @param CounselingConsent $consent
     * @param bool $isGranted
     * @param array $scopes
     * @return CounselingConsent
     */
    public function execute(CounselingConsent $consent, bool $isGranted, array $scopes = []): CounselingConsent
    {
        if (! $isGranted) {
            return app(RevokeConsentAction::class)->execute($consent);
        }

        $consent->status = ConsentStatus::GRANTED;
        $consent->scopes = $scopes;
        $consent->granted_at = now();
        $consent->rejected_at = null;
        $consent->revoked_at = null;
        $consent->save();

        $this->syncCounselingStatus($consent, true);

        app(RecordCaseEvent::class)->handle(
            CaseEventType::CONSENT_GRANTED,
            $consent->counseling?->sharing_id,
            $consent->counseling,
            payload: [
                'scopes'       => $scopes,
                'scopes_count' => count($scopes),
            ],
        );

        return $consent;
    }

    /**
     * Persetujuan data adalah gerbang rujukan: begitu siswa menyetujui,
     * ia berpindah ke tahap memilih jadwal atau melanjutkan jadwal yang sudah ada.
     */
    private function syncCounselingStatus(CounselingConsent $consent, bool $isGranted): void
    {
        $counseling = $consent->counseling;

        if (! $counseling || $counseling->status->isTerminal()) {
            return;
        }

        if (! $isGranted) {
            $counseling->update(['status' => CounselingStatus::MENUNGGU_DISKUSI->value]);

            return;
        }

        // Hanya rujukan eksternal yang memerlukan pemilihan jadwal oleh siswa.
        // Konseling internal jadwalnya sudah ditentukan Guru BK sejak awal.
        if ($counseling->type === 'external') {
            $latestBooking = $counseling->latestBookingSchedule;
            if ($latestBooking && $latestBooking->status === \App\Enums\BookingStatus::CONFIRMED) {
                $counseling->update(['status' => CounselingStatus::DIJADWALKAN->value]);
            } elseif ($latestBooking && $latestBooking->status === \App\Enums\BookingStatus::PENDING) {
                $counseling->update(['status' => CounselingStatus::MENUNGGU_KONFIRMASI->value]);
            } else {
                $counseling->update(['status' => CounselingStatus::MENUNGGU_JADWAL->value]);
            }

            if ($counseling->sharing) {
                $counseling->sharing->update([
                    'status' => $counseling->status === CounselingStatus::DIJADWALKAN
                        ? \App\Enums\ReportStatus::DIJADWALKAN->value
                        : \App\Enums\ReportStatus::MENUNGGU_PERSETUJUAN->value,
                ]);
            }
        } else {
            $counseling->update(['status' => CounselingStatus::DIJADWALKAN->value]);
        }
    }
}
