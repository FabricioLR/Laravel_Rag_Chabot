<?php

namespace App\Services\Embedding;

use App\Contracts\Embedding;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class OpenaiEmbedding implements Embedding
{
    public function generate(string $text, string $type = 'query'): array
    {
        try {
            $startTime = microtime(true);

            $model = config('services.openai.embedding_model', env('OPENAI_EMBEDDING_MODEL'));

            $response = OpenAI::embeddings()->create([
                'model' => $model,
                'input' => $text,
            ]);

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'vector' => $response->embeddings[0]->embedding,
                'duration' => $durationMs
            ];

        } catch (\Exception $e) {
            Log::error("OpenAI Embedding generation failed.", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}