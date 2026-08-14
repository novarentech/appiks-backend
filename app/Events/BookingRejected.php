<?php

namespace App\Events;

use App\Models\BookingSchedule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BookingSchedule $bookingSchedule) {}
}
