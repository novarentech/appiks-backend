<?php

namespace App\Enums;

enum CounselingMethod: string
{
    case OFFLINE = 'Tatap Muka';
    case ONLINE  = 'Video Call';
    case CHAT    = 'Chat';
}
