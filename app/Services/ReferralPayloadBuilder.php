<?php

namespace App\Services;

use App\Enums\MoodStatus;
use App\Models\Counseling;
use App\Models\MoodRecord;
use App\Models\Sharing;

class ReferralPayloadBuilder
{
    /**
     * Build rich structured payload for Referral Summary AI Generation.
     */
    public function buildPayload(Counseling $counseling): array
    {
        $consent = $counseling->latestConsent;
        $scopes = $consent ? ($consent->scopes ?? []) : [];
        $student = $counseling->student;

        // 1. Metadata dasar rujukan & anonimasi
        $payload = [
            'referral_id' => 'ref_' . substr(md5($counseling->id), 0, 8),
            'generated_at' => now()->toIso8601String(),
            'anonymous_student_id' => $student ? 'stu_' . substr(md5($student->id), 0, 8) : 'stu_anonymous',
            'student_grade' => $student?->room?->level ?? 'N/A',
            'referral_severity' => $counseling->sharing?->priority ?? ($counseling->severity_level ?? 'sedang'),
            'consent_scope' => $scopes,
            'not_shared_categories' => $this->getNotSharedCategories($scopes),
        ];

        if (!$student) {
            return $payload;
        }

        // 2. Modul Mood 30 Hari (jika scope mood_history diizinkan)
        if (in_array('mood_history', $scopes) || in_array('mood_history_30d', $scopes)) {
            $moodRecords = MoodRecord::where('user_id', $student->id)
                ->where('recorded', '>=', now()->subDays(30)->toDateString())
                ->orderBy('recorded', 'asc')
                ->get();

            $payload['mood_distribution_30d'] = $this->calculateMoodDistribution($moodRecords);
            
            $streaks = $this->calculateInsecureStreaks($moodRecords);
            $payload['tidak_aman_streak_max'] = $streaks['max'];
            $payload['tidak_aman_streak_current'] = $streaks['current'];
        }

        // 3. Modul Curhat & NLP 30 Hari (jika scope sharing_history/journal_excerpts diizinkan)
        if (in_array('sharing_history', $scopes) || in_array('journal_excerpts', $scopes) || in_array('sharings', $scopes) || in_array('incidents', $scopes)) {
            $sharings = Sharing::with('nlp')
                ->where('user_id', $student->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->orderByDesc('created_at')
                ->get();

            $redCount = 0;
            $yellowCount = 0;

            $payload['journal_excerpts'] = $sharings->map(function ($sharing) use (&$redCount, &$yellowCount) {
                $nlp = $sharing->nlp;
                $nlpResponse = $nlp?->response ?? [];

                // 1. Tentukan Zone dari NlpAnalysis (flag atau response['zone_status'])
                $zoneStatus = $nlp?->flag ?? ($nlpResponse['zone_status'] ?? null);
                
                $zone = match ($zoneStatus) {
                    'Red Zone', 'Red', 'tinggi' => 'Red',
                    'Yellow Zone', 'Yellow', 'sedang' => 'Yellow',
                    default => 'Green',
                };

                if ($zone === 'Red') {
                    $redCount++;
                } elseif ($zone === 'Yellow') {
                    $yellowCount++;
                }

                // 2. Dynamic Masking berdasarkan matched_keywords dari response NLP
                $matchedKeywords = $nlpResponse['matched_keywords'] ?? [];
                $maskedText = $this->maskDynamicNlpKeywords($sharing->description ?? '', $matchedKeywords);

                return [
                    'date' => $sharing->created_at ? $sharing->created_at->format('Y-m-d') : null,
                    'masked_text' => $maskedText,
                    'zone' => $zone,
                ];
            })->values()->all();

            $payload['red_zone_count_30d'] = $redCount;
            $payload['yellow_zone_count_30d'] = $yellowCount;
            $payload['total_sharings_30d'] = $sharings->count();
        }

        // 4. Modul Catatan Asesmen BK & Intervensi (jika scope assesment_logs/bk_assessment_notes diizinkan)
        if (in_array('assesment_logs', $scopes) || in_array('bk_assessment_notes', $scopes) || in_array('counseling_logs', $scopes)) {
            $logs = $counseling->logs()->orderBy('created_at', 'asc')->get();

            $payload['bk_assessment_notes'] = $logs->pluck('clinical_notes')->filter()->implode("\n; ");

            $payload['active_intervention_history'] = $logs->map(function ($log) {
                return [
                    'date' => $log->created_at ? $log->created_at->format('Y-m-d') : null,
                    'intervention_type' => 'Konseling Guru BK (' . ($log->session_mode ?? 'OFFLINE') . ')',
                    'outcome_summary' => ($log->resolution_status ?? 'Dalam Proses') . ($log->clinical_notes ? ': ' . substr($log->clinical_notes, 0, 100) : ''),
                ];
            })->values()->all();
        }

        return $payload;
    }

    /**
     * Hitung kategori data yang tidak dibagikan oleh siswa.
     */
    private function getNotSharedCategories(array $scopes): array
    {
        $notShared = [];

        if (!in_array('mood_history', $scopes) && !in_array('mood_history_30d', $scopes)) {
            $notShared[] = 'Riwayat Mood (mood_history)';
        }

        if (!in_array('sharing_history', $scopes) && !in_array('journal_excerpts', $scopes) && !in_array('sharings', $scopes) && !in_array('incidents', $scopes)) {
            $notShared[] = 'Curhat Siswa (journal_excerpts)';
        }

        if (!in_array('assesment_logs', $scopes) && !in_array('bk_assessment_notes', $scopes) && !in_array('counseling_logs', $scopes)) {
            $notShared[] = 'Catatan Konseling BK (bk_assessment_notes)';
        }

        return $notShared;
    }

    /**
     * Hitung distribusi mood per label status.
     */
    private function calculateMoodDistribution($moodRecords): array
    {
        $dist = [
            'Gembira' => 0,
            'Netral' => 0,
            'Sedih' => 0,
            'Marah' => 0,
        ];

        foreach ($moodRecords as $record) {
            $status = $record->status instanceof MoodStatus ? $record->status->value : (string) $record->status;
            match (strtolower($status)) {
                'happy', 'gembira' => $dist['Gembira']++,
                'neutral', 'netral' => $dist['Netral']++,
                'sad', 'sedih' => $dist['Sedih']++,
                'angry', 'marah' => $dist['Marah']++,
                default => null,
            };
        }

        return $dist;
    }

    /**
     * Hitung streak emosi tidak aman (sad / angry) terpanjang dan terkini.
     */
    private function calculateInsecureStreaks($moodRecords): array
    {
        $maxStreak = 0;
        $currentStreak = 0;
        $tempStreak = 0;

        foreach ($moodRecords as $record) {
            $status = $record->status instanceof MoodStatus ? $record->status->value : (string) $record->status;
            $isInsecure = in_array(strtolower($status), ['sad', 'angry', 'sedih', 'marah']);

            if ($isInsecure) {
                $tempStreak++;
                if ($tempStreak > $maxStreak) {
                    $maxStreak = $tempStreak;
                }
            } else {
                $tempStreak = 0;
            }
        }

        // Hitung current streak dari hari terbaru (belakang)
        $reversed = $moodRecords->reverse();
        foreach ($reversed as $record) {
            $status = $record->status instanceof MoodStatus ? $record->status->value : (string) $record->status;
            $isInsecure = in_array(strtolower($status), ['sad', 'angry', 'sedih', 'marah']);

            if ($isInsecure) {
                $currentStreak++;
            } else {
                break;
            }
        }

        return [
            'max' => $maxStreak,
            'current' => $currentStreak,
        ];
    }

    /**
     * Masking kata kunci sensitif dinamis berdasarkan matched_keywords dari hasil NLP Analysis.
     */
    private function maskDynamicNlpKeywords(string $text, array $matchedKeywords): string
    {
        // 1. Ambil kata kunci dari matched_keywords NLP response
        $keywords = [];
        foreach ($matchedKeywords as $item) {
            if (!empty($item['stem'])) {
                $keywords[] = $item['stem'];
            }
        }

        // Fallback kata kunci krisis umum jika NLP matched_keywords kosong
        if (empty($keywords)) {
            $keywords = [
                'bunuh diri', 'bunuh', 'mati', 'ujung nyawa', 'melukai diri',
                'self-harm', 'self harm', 'potong nadi', 'gantung diri', 'sayat',
                'racun', 'menyakiti diri', 'tidak ingin hidup', 'tidak mau hidup',
            ];
        }

        // 2. Ganti setiap kata kunci dengan frasa masking [MASKED:keyword]
        foreach (array_unique($keywords) as $keyword) {
            $pattern = '/' . preg_quote($keyword, '/') . '/i';
            $text = preg_replace($pattern, '[MASKED:keyword]', $text);
        }

        return $text;
    }
}
