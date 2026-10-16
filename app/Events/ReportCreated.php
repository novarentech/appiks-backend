<?php

namespace App\Events;

use App\Models\Counseling;
use App\Models\Report;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Counseling $report) {}
}
