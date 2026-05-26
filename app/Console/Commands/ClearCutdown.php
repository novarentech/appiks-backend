<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\Sharing;
use Illuminate\Console\Command;
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
    public function handle()
    {
        try {
            DB::beginTransaction();
            Sharing::where('cutdown_for_report','<',now())->update(['cutdown_for_report'=>null]);
            Report::where('cutdown_for_report','<',now())->update(['cutdown_for_report'=>null]);
            DB::commit();
            $this->info('Cutdown cleared successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to clear cutdown');
        }
    }
}
