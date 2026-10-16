<?php

namespace App\Enums;

enum ConsentStatus: string
{
    case PENDING = 'pending';
    case GRANTED = 'granted';
    case REJECTED = 'rejected';
    case REVOKED = 'revoked';

    public function isGranted(): bool
    {
        return $this === self::GRANTED;
    }
}
