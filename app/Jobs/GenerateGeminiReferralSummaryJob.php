<?php

namespace App\Jobs;

use App\Enums\ConsentStatus;
use App\Models\ClinicalSummary;
use App\Models\Counseling;
use App\Models\MoodRecord;
use App\Models\Sharing;
use App\Traits\InteractsWithGemini;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateGeminiReferralSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, InteractsWithGemini;

    public function __construct(public Counseling $counseling) {}

    public function handle(): void
    {
        $consent = $this->counseling->latestConsent;

        // If no consent granted, abort generation
        if (!$consent || $consent->status !== ConsentStatus::GRANTED) {
            return;
        }

        $builder = new \App\Services\ReferralPayloadBuilder();
        $payload = $builder->buildPayload($this->counseling);

        $systemInstruction = "Anda adalah asisten AI terintegrasi di APPIKS, sebuah platform kesehatan mental sekolah.\n\n"
        . "Tugas Anda adalah menghasilkan \"Ringkasan Naratif Rujukan\" untuk Psikolog Mitra berdasarkan data rujukan siswa.\n\n"
        . "SCOPE CONSENT KATEGORI DATA:\n"
        . "- Riwayat Mood (mood_history)\n"
        . "- Curhat Siswa (sharing_history)\n"
        . "- Catatan Konseling BK (assesment_logs)\n\n"
        . "ATURAN 1 (PENGGUNAAN DATA DAN EVALUASI CONSENT SCOPE):\n"
        . "- Evaluasi ketersediaan key pada JSON data mentah yang diberikan.\n"
        . "- Jika key kategori data ADA dalam data mentah (CONSENT = TRUE), rangkum data tersebut ke dalam narasi secara profesional.\n"
        . "- Jika key kategori data TIDAK ADA dalam data mentah (CONSENT = FALSE), DILARANG mengarang, menebak, atau menyebutkan isi data tersebut.\n\n"
        . "ATURAN 2 (PENYEBUTAN DATA YANG TIDAK DIBAGIKAN):\n"
        . "Secara eksplisit sebutkan kategori data (dari 3 scope: Riwayat Mood, Curhat Siswa, Catatan Konseling BK) yang tidak diizinkan atau tidak dibagikan oleh siswa jika key-nya tidak ada pada data mentah.\n\n"
        . "ATURAN 3 (DATA MASKING PADA RED ZONE):\n"
        . "Jika mengutip isi Curhat Red Zone, ganti kata-kata sensitif terkait bunuh diri, self-harm, kekerasan, atau nama spesifik orang lain dengan frasa \"[kata kunci disamarkan]\".\n\n"
        . "ATURAN 4 (DISCLAIMER WAJIB):\n"
        . "DILARANG melakukan diagnosis psikologis. Setiap ringkasan WAJIB diakhiri dengan disclaimer: \"Catatan: Ringkasan ini dibuat secara otomatis oleh sistem AI APPIKS untuk membantu rujukan dan bukan merupakan diagnosis psikologis resmi.\"\n\n"
        . "ATURAN 5 (FORMAT OUTPUT):\n"
        . "Hasilkan output hanya dalam 1 paragraf yang mengalir secara natural dan profesional. Jangan gunakan bullet points. Mulai selalu dengan format: \"Siswa kelas [Tingkat] berusia [Usia] tahun, dirujuk Guru BK dengan tingkat keparahan [Tingkat Keparahan].\"";

        $promptText = "Berikut adalah data mentah:\n" . json_encode($payload);

        // Call Gemini API via Trait
        $generatedText = $this->generateClinicalSummary($promptText, $systemInstruction);

        if ($generatedText) {
            // Server-side truncation fallback (maximum 200 words)
            $words = explode(' ', $generatedText);
            if (count($words) > 200) {
                $generatedText = implode(' ', array_slice($words, 0, 200)) . '...';
            }

            // Store in ClinicalSummary
            ClinicalSummary::updateOrCreate(
                ['counseling_id' => $this->counseling->id],
                [
                    'summary_data' => $generatedText,
                    'raw_payload' => $payload,
                ]
            );
        }
    }
}
