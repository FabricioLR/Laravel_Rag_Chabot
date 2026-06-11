<?php

namespace App\Services\Embedding;

use App\Contracts\Embedding;
use InvalidArgumentException;

class EmbeddingManager
{
    public static function make(): Embedding
    {
        $provider = config('services.embedding.provider', env('EMBEDDING_PROVIDER', 'huggingface'));

        return match (strtolower($provider)) {
            'huggingface' => new HuggingFace(),
            //'openai'      => new OpenAIDriver(),
            default       => throw new InvalidArgumentException("Embedding Driver [{$provider}] is not supported."),
        };
    }
}