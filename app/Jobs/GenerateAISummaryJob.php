<?php

namespace App\Jobs;

use App\Models\ClinicalSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAISummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $counselingId;
    protected string $sanitizedText;

    /**
     * Create a new job instance.
     */
    public function __construct(int $counselingId, string $sanitizedText)
    {
        $this->counselingId = $counselingId;
        $this->sanitizedText = $sanitizedText;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Simulate Clinical AI analysis using a high-quality dummy response
        $dummyReport = "=== CLINICAL AI SUMMARY REPORT ===\n" .
            "Date Generated: " . now()->toDateTimeString() . "\n" .
            "Status: Anonymized Analysis Complete\n\n" .
            "1. REASON FOR REFERRAL & EMOTIONAL SPECTRUM\n" .
            "Student demonstrates a recurring emotional pattern of heightened sensitivity to stress. " .
            "Frequent triggers relate to scholastic milestones and expectations.\n\n" .
            "2. SENTIMENT TRENDS\n" .
            "Anonymized journal entries exhibit high variance in mood records. " .
            "Venting occurrences peaks around late-night hours, suggesting potential sleep cycle disruption linked with stress.\n\n" .
            "3. POTENTIAL RISKS & RED ZONES\n" .
            "Identified occasional Red Zone triggers, primarily characterized by feelings of academic inadequacy and isolation. " .
            "However, cognitive reframing tendencies are present in recent entries.\n\n" .
            "4. CLINICAL RECOMMENDATIONS FOR PARTNER PSYCHOLOGIST\n" .
            "- Establish a safe, non-judgmental space focusing on anxiety mitigation techniques.\n" .
            "- Introduce cognitive-behavioral tools for coping with academic stressors.\n" .
            "- Monitor sleep hygiene and self-care routines.\n\n" .
            "--- End of Report ---";

        ClinicalSummary::updateOrCreate(
            ['counseling_id' => $this->counselingId],
            ['summary_data' => $dummyReport]
        );
    }
}
