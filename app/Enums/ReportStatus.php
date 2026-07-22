<?php

namespace App\Enums;

enum ReportStatus: string
{
    case MENUNGGU_TINJAUAN     = 'Belum Ditinjau';
    case DITINJAU              = 'Sedang Ditangani';
    case MENUNGGU_TANGGAPAN    = 'Belum Ditanggapi';
    case MENUNGGU_PERSETUJUAN = 'Menunggu Persetujuan Siswa';
    case DIJADWALKAN           = 'Konseling Dijadwalkan';
    case SELESAI               = 'Diselesaikan';
    case DIBATALKAN            = 'Dibatalkan';
    case DITOLAK             = 'Jadwal Ditolak Siswa';
}
