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
        try{
            $startTime = microtime(true);
            $apiKey = config('services.huggingface.key', env('HUGGINGFACE_API_KEY'));
            $model = config('services.huggingface.embedding_model', env('HUGGINGFACE_EMBEDDING_MODEL', 'intfloat/multilingual-e5-large'));

            $prefix = ($type === 'passage') ? 'passage: ' : 'query: ';

            $response = Http::withToken($apiKey)
                ->post('https://router.huggingface.co/hf-inference/models/' . $model . '/pipeline/feature-extraction', [
                    'inputs' => $prefix . $text,
                ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->failed()) {
                Log::error('Failed to generate text embeddings via Hugging Face.', [
                    'duration_ms' => $duration,
                    'status'      => $response->status(),
                    'response'    => $response->json() ?? $response->body()
                ]);
                throw new Exception('Failed to generate text embeddings.');
            }

            return [
                'vector' => $response->json(),
                'duration' => $duration
            ];
        } catch (\Exception $e) {
            Log::error("Hugging Face Embedding generation failed.", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}