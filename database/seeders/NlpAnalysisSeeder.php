<?php

namespace Database\Seeders;

use App\Enums\NlpAnalysisStatus;
use App\Models\NlpAnalysis;
use App\Models\Sharing;
use Illuminate\Database\Seeder;

class NlpAnalysisSeeder extends Seeder
{
    /**
     * Seeds NLP analyses for all existing sharings.
     *
     * Scenarios:
     *   - sharing.priority = 'rendah'  → safe  → true-negative (no flag)
     *   - sharing.priority = 'tinggi'  → unsafe → true-positive  (flag = 'high-risk')
     *
     * A small fraction of safe sharings also gets false-positive (NLP wrongly flagged),
     * and a small fraction of unsafe sharings gets false-negative (NLP missed it).
     * This mirrors real-world NLP imperfection.
     */
    public function run(): void
    {
        $sharings = Sharing::withTrashed()->get();

        if ($sharings->isEmpty()) {
            $this->command->warn('NlpAnalysisSeeder: No sharings found. Run SharingSeeder first.');
            return;
        }

        $inserts = [];

        foreach ($sharings as $index => $sharing) {
            $isHighPriority = $sharing->priority === 'tinggi';

            // Rotate through NLP statuses for variety
            // High-priority sharings → mostly true-positive, occasionally false-negative
            // Low-priority sharings  → mostly true-negative, occasionally false-positive
            $status = $this->resolveNlpStatus($isHighPriority, $index);
            $isReallyFlagged = in_array($status, [
                NlpAnalysisStatus::TRUE_POSITIVE->value,
                NlpAnalysisStatus::FALSE_POSITIVE->value,
            ]);

            $inserts[] = [
                'text'         => $sharing->description,
                'response'     => json_encode($this->buildNlpResponse($isReallyFlagged)),
                'flag'         => $isReallyFlagged ? 'high-risk' : null,
                'status'       => $status,
                'reason'       => $isReallyFlagged
                    ? 'Terdeteksi kata kunci berisiko tinggi pada teks curhat siswa.'
                    : null,
                'nlpable_type' => Sharing::class,
                'nlpable_id'   => $sharing->id,
                'created_at'   => $sharing->created_at,
                'updated_at'   => $sharing->updated_at,
                'deleted_at'   => null,
            ];
        }

        // Batch insert for performance
        foreach (array_chunk($inserts, 100) as $chunk) {
            NlpAnalysis::insert($chunk);
        }

        $this->command->info("NlpAnalysisSeeder: {$sharings->count()} NLP analyses seeded.");
    }

    /**
     * Determine NLP status based on actual priority and rotation index.
     */
    private function resolveNlpStatus(bool $isHighPriority, int $index): string
    {
        if ($isHighPriority) {
            // ~80% true-positive, ~20% false-negative
            return ($index % 5 === 0)
                ? NlpAnalysisStatus::FALSE_NEGATIVE->value
                : NlpAnalysisStatus::TRUE_POSITIVE->value;
        }

        // ~80% true-negative, ~20% false-positive
        return ($index % 5 === 0)
            ? NlpAnalysisStatus::FALSE_POSITIVE->value
            : NlpAnalysisStatus::TRUE_NEGATIVE->value;
    }

    /**
     * Build a realistic NLP response payload.
     */
    private function buildNlpResponse(bool $flagged): array
    {
        if ($flagged) {
            return [
                'total_score'      => rand(70, 100),
                'zone_status'      => 'red',
                'matched_keywords' => [
                    ['stem' => 'tidak bisa', 'zone' => 'red', 'weight' => 30],
                    ['stem' => 'menyerah',   'zone' => 'red', 'weight' => 25],
                    ['stem' => 'lelah',      'zone' => 'yellow', 'weight' => 15],
                ],
            ];
        }

        return [
            'total_score'      => rand(0, 30),
            'zone_status'      => 'green',
            'matched_keywords' => [],
        ];
    }
}
