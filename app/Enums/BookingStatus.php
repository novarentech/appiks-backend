<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING   = 'pending';
    case CONFIRMED = 'confirmed';
    case REJECTED  = 'rejected';
    case EXPIRED   = 'expired';
}
