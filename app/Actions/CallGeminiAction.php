<?php

namespace App\Actions;

use App\Events\GeminiTokenUsed;
use Gemini\Data\GenerationConfig;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\DB;

class CallGeminiAction
{
    public function handle(string $prompt, int $maxTokens = 3000): string
    {
        $currentToken = DB::table('gemini_api_token')->where('used', true)->first();
        config(['gemini.api_key' => $currentToken->token]);

        $model = Gemini::generativeModel('models/gemini-2.0-flash');
        $config = new GenerationConfig(maxOutputTokens: $maxTokens);
        $result = $model->withGenerationConfig($config)->generateContent($prompt)->text();

        $tokenCount = $model->countTokens($prompt)->totalTokens;
        GeminiTokenUsed::dispatch($currentToken->id, $tokenCount);

        return $result;
    }
}
