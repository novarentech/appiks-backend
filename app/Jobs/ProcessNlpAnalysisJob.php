<?php

namespace App\Jobs;

use App\Actions\CallNlpAction;
use App\Models\NlpAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNlpAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected NlpAnalysis $nlpAnalysis) {}

    /**
     * Execute the job.
     */
    public function handle(CallNlpAction $callNlpAction): void
    {
        $response = $callNlpAction->handle($this->nlpAnalysis->text);

        $this->nlpAnalysis->update([
            'response' => $response,
            'flag' => $response['zone_status'] ?? null,
        ]);
    }
}
