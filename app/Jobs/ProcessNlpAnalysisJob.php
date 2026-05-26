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

    protected array $zoneMapping = [
        'Red Zone' => 'tinggi',
        'Yellow Zone' => 'sedang',
        'No Trigger' => 'rendah',
    ];

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

        $this->nlpAnalysis->nlpable()?->update([
            'cutdown_for_report' => now()->addHours(48),
            'priority' => $this->zoneMapping[$response['zone_status']],
        ]);
    }
}
