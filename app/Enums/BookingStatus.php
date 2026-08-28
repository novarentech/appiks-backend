<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING   = 'pending';
    case FINISHED   = 'finished';
    case CONFIRMED = 'confirmed';
    case REJECTED  = 'rejected';
    case EXPIRED   = 'expired';
}
