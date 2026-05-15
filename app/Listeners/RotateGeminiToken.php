<?php

namespace App\Listeners;

use App\Events\GeminiTokenUsed;
use Illuminate\Support\Facades\DB;

class RotateGeminiToken
{
    public function handle(GeminiTokenUsed $event): void
    {
        DB::table('gemini_api_token')
            ->where('id', $event->tokenId)
            ->update(['quota' => DB::raw("quota + {$event->tokensUsed}"), 'used' => false]);

        $nextToken = DB::table('gemini_api_token')
            ->where('id', '>', $event->tokenId)
            ->orderBy('id')
            ->first()
            ?? DB::table('gemini_api_token')->orderBy('id')->first();

        DB::table('gemini_api_token')
            ->where('id', $nextToken->id)
            ->update(['used' => true]);
    }
}
