<?php

namespace App\Observers;

use App\Jobs\ProcessNlpAnalysisJob;
use App\Models\Sharing;

class SharingObserver
{
    /**
     * Handle the Sharing "created" event.
     */
    public function created(Sharing $sharing): void
    {
        $nlpAnalysis = $sharing->nlp()->create([
            'text' => $sharing->description,
        ]);

        ProcessNlpAnalysisJob::dispatch($nlpAnalysis);
    }
}
