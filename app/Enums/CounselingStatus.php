<?php

namespace App\Enums;

enum CounselingStatus: string
{
    case MENUNGGU             = 'menunggu';
    case MENUNGGU_JADWAL      = 'menunggu_jadwal';
    case MENUNGGU_KONFIRMASI  = 'menunggu_konfirmasi';
    case DIJADWALKAN          = 'dijadwalkan';
    case DIJADWAL_ULANG       = 'dijadwal_ulang';
    case SELESAI              = 'selesai';
    case DITOLAK              = 'ditolak';
    case DIBATALKAN           = 'dibatalkan';

    /**
     * Kasus masih berjalan dan menunggu tindakan dari salah satu pihak.
     */
    public function isActive(): bool
    {
        return ! $this->isTerminal();
    }

    /**
     * Kasus sudah ditutup dan tidak akan berpindah status lagi.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::SELESAI, self::DITOLAK, self::DIBATALKAN => true,
            default => false,
        };
    }

    /**
     * Status yang menunggu tindakan siswa.
     */
    public function needsStudentAction(): bool
    {
        return match ($this) {
            self::MENUNGGU, self::MENUNGGU_JADWAL, self::DIJADWAL_ULANG => true,
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function activeValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->isActive())
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function terminalValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->isTerminal())
        ));
    }
}
