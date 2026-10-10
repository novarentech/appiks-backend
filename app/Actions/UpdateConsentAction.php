<?php

namespace App\Actions;

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
        if ($isGranted) {
            $consent->status = ConsentStatus::GRANTED;
            $consent->scopes = $scopes;
            $consent->granted_at = now();
            $consent->rejected_at = null;
            $consent->save();
        } else {
            $consent->status = ConsentStatus::REJECTED;
            $consent->rejected_at = now();
            $consent->granted_at = null;
            $consent->scopes = null;
            $consent->save();
        }

        $this->syncCounselingStatus($consent, $isGranted);

        return $consent;
    }

    /**
     * Persetujuan data adalah gerbang pertama sebuah rujukan: begitu siswa
     * menyetujui, ia berpindah ke tahap memilih jadwal; begitu siswa menolak,
     * rujukannya berhenti.
     */
    private function syncCounselingStatus(CounselingConsent $consent, bool $isGranted): void
    {
        $counseling = $consent->counseling;

        if (! $counseling || $counseling->status->isTerminal()) {
            return;
        }

        if (! $isGranted) {
            $counseling->update(['status' => CounselingStatus::DITOLAK->value]);

            return;
        }

        // Hanya rujukan eksternal yang memerlukan pemilihan jadwal oleh siswa.
        // Konseling internal jadwalnya sudah ditentukan Guru BK sejak awal.
        if ($counseling->type === 'external') {
            $counseling->update(['status' => CounselingStatus::MENUNGGU_JADWAL->value]);
        }
    }
}
