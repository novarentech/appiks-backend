<?php

namespace App\Enums;

enum ConsentStatus: string
{
    case PENDING = 'pending';
    case GRANTED = 'granted';
    case REJECTED = 'rejected';
}
