<?php

namespace App\Console\Commands;

use App\Actions\AnalyzeInsecurePersonaAction;
use App\Actions\GenerateMissionAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateArchtype extends Command
{
    protected $signature = 'generate:archtype';

    protected $description = 'Get ai generated archtype persona';

    public function handle(AnalyzeInsecurePersonaAction $personaAction, GenerateMissionAction $missionAction)
    {
        if (DB::table('ai_generated')->whereNull('answer')->count()) {
            $need_generates = DB::table('ai_generated')->whereNull('answer')->inRandomOrder()->first();
            $this->info('Get the null value');
        } else {
            $need_generates = DB::table('ai_generated')->orderBy('updated_at', 'asc')->inRandomOrder()->first();
            $this->info('Update the oldest value');
            // Reset answer to null to force regeneration in Action
            DB::table('ai_generated')->where('id', $need_generates->id)->update(['answer' => null]);
        }

        try {
            if (strlen($need_generates->key) == '5') {
                $this->info('Get first test');
                $personaAction->handle(str_split($need_generates->key));
            } else {
                $this->info('Get third test');
                $missionAction->handleFuel(str_split($need_generates->key));
            }
            $this->info('Finish');
        } catch (\Throwable $th) {
            $this->info($th->getMessage());
        }
    }
}
