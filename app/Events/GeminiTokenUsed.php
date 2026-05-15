<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class GeminiTokenUsed
{
    use Dispatchable;

    public function __construct(
        public readonly int $tokenId,
        public readonly int $tokensUsed,
    ) {}
}
