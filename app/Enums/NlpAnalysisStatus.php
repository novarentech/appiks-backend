<?php

namespace App\Enums;

enum NlpAnalysisStatus: string
{
    case FALSE_POSITIVE = 'false-positive';
    case FALSE_NEGATIVE = 'false-negative';
    case TRUE_POSITIVE  = 'true-positive';
    case TRUE_NEGATIVE  = 'true-negative';
}
