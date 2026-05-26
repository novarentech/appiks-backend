<?php

namespace App\Enums;

enum CounselingStatus: string
{
    case DIJADWALKAN = 'dijadwalkan';
    case MENUNGGU    = 'menunggu';
    case SELESAI     = 'selesai';
    case DITOLAK     = 'ditolak';   
}
