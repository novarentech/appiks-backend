<?php

namespace App\Enums;

enum SharingAction: string
{
    case INTERNAL = "konseling_mandiri";
    case MEDIC = "penanganan_medis";
    case OTHER = "lainnya";
}
