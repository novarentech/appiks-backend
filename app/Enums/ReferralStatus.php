<?php

namespace App\Enums;

enum ReferralStatus: string
{
    case DRAFT = 'draft';
    case CONSENT_GRANTED = 'consent_granted';
    case CONSENT_REJECTED = 'consent_rejected';
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
}
