<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class CallNlpAction
{
    /**
     * Analyze Indonesian text for distress levels using the external NLP service.
     *
     * @param string $text
     * @return array
     * @throws RequestException
     */
    public function handle(string $text): array
    {
        $url = config('services.nlp.url') . '/api/analyze';
        $key = config('services.nlp.key');

        try {
            $request = Http::withHeaders([
                'X-APPIKS-NLP-KEY' => $key,
            ]);

            if (app()->environment('local', 'testing')) {
                $request->withoutVerifying();
            }

            $response = $request->post($url, [
                'text' => $text,
            ]);

            if ($response->failed()) {
                Log::error('NLP Service Analysis failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'text_preview' => mb_substr($text, 0, 100),
                ]);
                $response->throw();
            }

            return $response->json();
        } catch (\Throwable $exception) {
            Log::error('NLP Service Exception occurred', [
                'message' => $exception->getMessage(),
                'text_preview' => mb_substr($text, 0, 100),
            ]);
            throw $exception;
        }
    }
}
