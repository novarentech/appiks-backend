<?php

namespace App\Enums;

enum SlotStatus: string
{
    case AVAILABLE  = 'available';
    case TENTATIVE  = 'tentative';
    case CONFIRMED  = 'confirmed';
}
