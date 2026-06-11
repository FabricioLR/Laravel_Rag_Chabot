<?php

namespace App\Services\LLM;

use App\Contracts\LLM;
use InvalidArgumentException;

class LLMManager
{
    public static function make(): LLM
    {
        $provider = config('services.llm.provider', env('LLM_PROVIDER', 'groq'));

        return match (strtolower($provider)) {
            'groq'   => new Groq(),
            //'openai' => new OpenAIDriver(),
            default  => throw new InvalidArgumentException("LLM Driver [{$provider}] is not supported."),
        };
    }
}