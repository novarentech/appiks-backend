<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING     = 'pending';
    case CONFIRMED   = 'confirmed';
    case RESCHEDULED = 'rescheduled';
    case REJECTED    = 'rejected';
    case EXPIRED     = 'expired';
    case FINISHED    = 'finished';

    /**
     * Booking masih menahan slot psikolog.
     */
    public function holdsSlot(): bool
    {
        return match ($this) {
            self::PENDING, self::CONFIRMED, self::FINISHED => true,
            default => false,
        };
    }

    /**
     * Booking yang sesinya sudah atau akan benar-benar berjalan,
     * sehingga data klinis boleh diakses psikolog.
     */
    public function isActionable(): bool
    {
        return match ($this) {
            self::CONFIRMED, self::FINISHED => true,
            default => false,
        };
    }

    /**
     * Slot sudah lepas dari booking ini dan boleh ditawarkan ulang.
     */
    public function releasesSlot(): bool
    {
        return ! $this->holdsSlot();
    }

    /**
     * @return array<int, string>
     */
    public static function holdsSlotValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->holdsSlot())
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function actionableValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->isActionable())
        ));
    }
}
