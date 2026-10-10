<?php

namespace App\Console\Commands;

use App\Actions\RecordCaseEvent;
use App\Enums\CaseEventType;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Sharing;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClearCutdown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cutdown:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cutdown for Report and Sharing';

    /**
     * Execute the console command.
     */
    public function handle(RecordCaseEvent $recordEvent)
    {
        try {
            DB::beginTransaction();

            // Dicatat per baris sebelum tenggatnya dinolkan. Tanpa ini, bukti
            // bahwa SLA pernah terlampaui hilang bersama kolomnya.
            $elapsed = Sharing::where('cutdown_for_report', '<', now())->get();

            foreach ($elapsed as $sharing) {
                $recordEvent->handle(CaseEventType::SLA_DEADLINE_ELAPSED, $sharing, payload: [
                    // cutdown_for_report tidak ada di $casts, jadi nilainya string.
                    'deadline_at' => Carbon::parse($sharing->cutdown_for_report)->toIso8601String(),
                ]);
            }

            Sharing::where('cutdown_for_report', '<', now())->update(['cutdown_for_report' => null]);
            Report::where('cutdown_for_report', '<', now())->update(['cutdown_for_report' => null]);
            DB::commit();
            $this->info('Cutdown cleared successfully ('.$elapsed->count().' SLA terlampaui dicatat)');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to clear cutdown');
        }
    }
}
