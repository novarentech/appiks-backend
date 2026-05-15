<?php

namespace App\Enums;

enum ReportStatus: string
{
    case DIJADWALKAN = 'dijadwalkan';
    case MENUNGGU    = 'menunggu';
    case SELESAI     = 'selesai';
    case DIBATALKAN  = 'dibatalkan';
}
