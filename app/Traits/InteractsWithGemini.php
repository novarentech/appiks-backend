<?php

namespace App\Traits;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

trait InteractsWithGemini
{
    /**
     * Generate clinical summary using the official Gemini SDK.
     */
    public function generateClinicalSummary(string $promptText, string $systemInstruction): ?string
    {
        try {
            if(config('gemini.api_key') == ""){
                return "===Hasil AI===";
            }
            $result = Gemini::generativeModel('gemini-3.5-flash-lite')
                ->generateContent("INSTRUCTION:\n{$systemInstruction}\n\nDATA:\n{$promptText}");

            return $result->text();
        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return null;
        }
    }
}
