<?php

namespace App\Events;

use App\Models\Counseling;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CounselingScheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Counseling $counseling) {}
}
