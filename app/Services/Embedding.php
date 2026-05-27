<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class Embedding
{
    public function generate(string $text): array
    {
        $startTime = microtime(true);
        $apiKey = config('services.huggingface.api_key', env('HUGGINGFACE_API_KEY'));

        $response = Http::withToken($apiKey)
            ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-large/pipeline/feature-extraction', [
                'inputs' => "query: " . $text,
            ]);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($response->failed()) {
            Log::error('Failed to generate text embeddings via Hugging Face.', [
                'duration_ms' => $duration,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new Exception('Failed to generate text embeddings.');
        }

        Log::info('Hugging Face embeddings retrieved successfully.', ['duration_ms' => $duration]);

        return [
            'vector' => $response->json(),
            'duration' => $duration
        ];
    }
}