<?php

namespace App\Jobs;

use App\Enums\ConsentStatus;
use App\Actions\RecordCaseEvent;
use App\Enums\CaseEventType;
use App\Models\ClinicalSummary;
use App\Models\Counseling;
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

        $disclaimer = "\n\nCatatan: Ringkasan ini dibuat secara otomatis oleh sistem AI APPIKS untuk membantu rujukan dan bukan merupakan diagnosis psikologis resmi.";

        $systemInstruction = "Anda adalah asisten AI terintegrasi di APPIKS, sebuah platform kesehatan mental sekolah.\n\n"
        . "Tugas Anda adalah menghasilkan \"Ringkasan Naratif Rujukan\" untuk Psikolog Mitra berdasarkan data rujukan siswa secara objektif dan faktual.\n\n"
        . "STRUKTUR DATA PAYLOAD:\n"
        . "- Metadata Rujukan: student_grade (Tingkat kelas siswa), referral_severity (Tingkat keparahan rujukan)\n"
        . "- Riwayat Mood (jika tersedia): mood_distribution_30d, tidak_aman_streak_max, tidak_aman_streak_current\n"
        . "- Curhat Siswa (jika tersedia): journal_excerpts (berisi date, masked_text, zone), red_zone_count_30d, yellow_zone_count_30d, total_sharings_30d\n"
        . "- Catatan Konseling BK (jika tersedia): bk_assessment_notes, active_intervention_history\n\n"
        . "ATURAN 1 (PENGGUNAAN DATA FAKTUAL):\n"
        . "- Evaluasi ketersediaan key pada JSON data mentah yang diberikan.\n"
        . "- Rangkum hanya data yang tersedia ke dalam narasi secara profesional.\n"
        . "- DILARANG mengarang, menebak, atau menambahkan informasi yang tidak ada dalam data mentah.\n\n"
        . "ATURAN 2 (KURASI CURHAT - HANYA YELLOW DAN RED ZONE):\n"
        . "- Dari data journal_excerpts, rangkum HANYA curhat yang berstatus Yellow Zone atau Red Zone sebagai \"Kutipan curhat yang terdeteksi memerlukan perhatian (30 hari terakhir)\".\n"
        . "- JANGAN masukkan curhat berstatus Green Zone (tanpa trigger).\n"
        . "- Tampilkan teks apa adanya secara objektif tanpa melakukan penyamaran kata sensitif.\n\n"
        . "ATURAN 3 (DILARANG DIAGNOSIS & REKOMENDASI TINDAKAN):\n"
        . "- DILARANG melakukan diagnosis psikologis resmi atau menyebutkan label gangguan mental.\n"
        . "- DILARANG memberikan rekomendasi tindakan, intervensi, maupun saran terapi (misalnya CBT atau teknik konseling lainnya). Tugas Anda HANYA merangkum data fakta yang ada.\n\n"
        . "ATURAN 4 (DISCLAIMER WAJIB):\n"
        . "Setiap ringkasan WAJIB diakhiri dengan disclaimer: \"" . $disclaimer . "\"\n\n"
        . "ATURAN 5 (FORMAT OUTPUT):\n"
        . "Hasilkan output hanya dalam 1 paragraf yang mengalir secara natural dan profesional. Jangan gunakan bullet points. Mulai selalu dengan format: \"Siswa kelas [Tingkat], dirujuk Guru BK dengan tingkat keparahan [Tingkat Keparahan].\"";

        $promptText = "Berikut adalah data mentah:\n" . json_encode($payload);

        // Call Gemini API via Trait
        $generatedText = $this->generateClinicalSummary($promptText, $systemInstruction);

        if ($generatedText) {
            // Server-side truncation fallback (maximum 200 words) tanpa memotong disclaimer
            $trimmedText = trim($generatedText);
            if (str_ends_with($trimmedText, $disclaimer)) {
                $narrative = trim(substr($trimmedText, 0, -strlen($disclaimer)));
            } else {
                $narrative = $trimmedText;
            }

            $words = preg_split('/\s+/', $narrative, -1, PREG_SPLIT_NO_EMPTY);
            $disclaimerWords = preg_split('/\s+/', $disclaimer, -1, PREG_SPLIT_NO_EMPTY);
            $maxNarrativeWords = max(1, 200 - count($disclaimerWords));

            if (count($words) > $maxNarrativeWords) {
                $narrative = implode(' ', array_slice($words, 0, $maxNarrativeWords)) . '...';
            }

            $generatedText = rtrim($narrative) . ' ' . $disclaimer;
        }

        // Store in ClinicalSummary
        ClinicalSummary::updateOrCreate(
            ['counseling_id' => $this->counseling->id],
            [
                'summary_data' => $generatedText ?? '',
                'raw_payload' => $payload,
            ]
        );

        app(RecordCaseEvent::class)->handle(
            $generatedText ? CaseEventType::AI_SUMMARY_GENERATED : CaseEventType::AI_SUMMARY_FAILED,
            $this->counseling->sharing_id,
            $this->counseling,
        );
        
    }
}
