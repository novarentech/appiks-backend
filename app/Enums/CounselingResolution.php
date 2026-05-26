<?php

namespace App\Enums;

enum CounselingResolution: string
{
    case NOTCRITICAL = "Bukan Kondisi Kritis (Red Zone)";
    case NOTPRIORITY = "Bukan Kondisi Prioritas (Yellow Zone)";
    case NEEDMORE    = "Perlu Rujukan Professional";
}
