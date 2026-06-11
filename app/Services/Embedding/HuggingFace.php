<?php

namespace App\Services\Embedding;

use App\Contracts\Embedding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class HuggingFace implements Embedding
{
    public function generate(string $text, string $type = 'query'): array
    {
        $startTime = microtime(true);
        $apiKey = config('services.huggingface.api_key', env('HUGGINGFACE_API_KEY'));

        $prefix = ($type === 'passage') ? 'passage: ' : 'query: ';

        $response = Http::withToken($apiKey)
            ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-large/pipeline/feature-extraction', [
                'inputs' => $prefix . $text,
            ]);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($response->failed()) {
            Log::error('Failed to generate text embeddings via Hugging Face.', [
                'duration_ms' => $duration,
                'status' => $response->status(),
            ]);
            throw new Exception('Failed to generate text embeddings.');
        }

        return [
            'vector' => $response->json(),
            'duration' => $duration
        ];
    }
}