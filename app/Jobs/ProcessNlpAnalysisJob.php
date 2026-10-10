<?php

namespace App\Jobs;

use App\Actions\RecordCaseEvent;
use App\Enums\CaseEventType;
use App\Actions\CallNlpAction;
use App\Enums\ReportStatus;
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
    public function handle(CallNlpAction $callNlpAction, RecordCaseEvent $recordEvent): void
    {
        $parent = $this->nlpAnalysis->nlpable;

        try {
            $response = $callNlpAction->handle($this->nlpAnalysis->text);
        } catch (\Throwable $e) {
            // Kegagalan NLP sebelumnya tidak meninggalkan jejak apa pun: curhat
            // tersimpan tanpa prioritas dan tanpa tenggat, tidak bisa dibedakan
            // dari curhat yang memang aman.
            $recordEvent->handle(
                CaseEventType::NLP_ANALYSIS_FAILED,
                $parent instanceof \App\Models\Sharing ? $parent : null,
            );

            throw $e;
        }

        switch ($response['zone_status']) {
            case 'Red Zone':
                $cutdown = 2;
                break;
            case 'Yellow Zone':
                $cutdown = 72;
                break;
            case 'No Trigger':
                $cutdown = 0;
                break;
        }

        $this->nlpAnalysis->update([
            'response' => $response,
            'flag' => $response['zone_status'] ?? null,
        ]);

        $deadline = now()->addHours($cutdown);

        $this->nlpAnalysis->nlpable()?->update([
            'cutdown_for_report' => $deadline,
            'priority' => $this->zoneMapping[$response['zone_status']],
        ]);

        $recordEvent->handle(
            CaseEventType::NLP_ANALYZED,
            $parent instanceof \App\Models\Sharing ? $parent : null,
            payload: [
                'zone'         => $response['zone_status'] ?? null,
                'priority'     => $this->zoneMapping[$response['zone_status']] ?? null,
                'total_score'  => $response['total_score'] ?? null,
                'sla_hours'    => $cutdown,
                'sla_deadline' => $cutdown > 0 ? $deadline->toIso8601String() : null,
            ],
        );

        if ($response['zone_status'] == 'No Trigger') {
            $this->nlpAnalysis->nlpable()?->update([
                'status' => ReportStatus::MENUNGGU_TANGGAPAN->value,
            ]);
        }
    }
}
