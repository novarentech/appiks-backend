<?php

namespace App\Events;

use App\Models\CounselingLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CounselingLogStored
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CounselingLog $counselingLog) {}
}
