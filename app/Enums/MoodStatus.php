<?php

namespace App\Enums;

enum MoodStatus: string
{
    case HAPPY   = 'happy';
    case NEUTRAL = 'neutral';
    case SAD     = 'sad';
    case ANGRY   = 'angry';

    public function isSecure(): bool
    {
        return match($this) {
            self::HAPPY, self::NEUTRAL => true,
            default => false,
        };
    }

    public function label(): string
    {
        return $this->isSecure() ? 'Aman' : 'Tidak Aman';
    }

    public static function secureValues(): array
    {
        return [self::HAPPY->value, self::NEUTRAL->value];
    }

    public static function insecureValues(): array
    {
        return [self::SAD->value, self::ANGRY->value];
    }
}
