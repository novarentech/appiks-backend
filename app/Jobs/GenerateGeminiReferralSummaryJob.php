<?php

namespace App\Jobs;

use App\Enums\ConsentStatus;
use App\Models\ClinicalSummary;
use App\Models\Counseling;
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

        $scopes = $consent->scopes ?? [];
        $payload = [];
        $student = $this->counseling->student;

        // Aggregation strictly based on consent scopes
        if (in_array('counseling_logs', $scopes)) {
            $payload['logs'] = $this->counseling->logs()
                ->select('session_mode', 'clinical_notes', 'created_at')
                ->get();
        }

        if (in_array('sharings', $scopes) || in_array('incidents', $scopes)) {
            $payload['incidents'] = Sharing::where('user_id', $student->id)
                ->select('description', 'priority', 'created_at')
                ->get();
        }

        // Prompt Engineering
        $systemInstruction = "Anda adalah asisten perangkum data faktual. Tugas Anda adalah merangkum log obrolan dan catatan perilaku siswa untuk membantu persiapan psikolog klinis.\nATURAN MUTLAK:\n- Panjang teks MAKSIMAL 200 kata.\n- Gunakan Bahasa Indonesia formal, profesional, dan objektif.\n- Anda DILARANG KERAS memberikan diagnosis klinis, menyebutkan nama gangguan mental, atau memberikan saran pengobatan.\n- Rangkum HANYA fakta, pemicu (triggers) yang terlihat dari teks, dan tindakan awal berdasarkan data yang diberikan.\n- Jangan menambahkan opini, asumsi, atau informasi di luar data yang diberikan.";

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
