<?php

namespace App\Services\LLM;

use App\Contracts\LLM;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class Groq implements LLM
{
    public function generateAnswer(string $prompt, $systemPrompt, string $sessionId): array
    {
        try{
            $startTime = microtime(true);
            $apiKey = config('services.groq.key', env('GROQ_API_KEY'));
            $model = config('services.groq.llm_model', env('GROQ_LLM_MODEL', 'llama-3.1-8b-instant'));
            $temperature = config('services.groq.llm_model_temperature', env('GROQ_LLM_MODEL_TEMPERATURE', 0.1));
            $maxOutputTokens = config('services.groq.llm_model_max_output_tokens', env('GROQ_LLM_MODEL_MAX_OUTPUT_TOKENS', 1024));

            $response = Http::withToken($apiKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => (float)$temperature,
                    'max_tokens' => (int)$maxOutputTokens
                ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->failed()) { 
                Log::error('Groq LLM API call failed catastrophically.', [
                    'session_id'  => $sessionId,
                    'duration_ms' => $duration,
                    'status'      => $response->status(),
                    'response'    => $response->json() ?? $response->body()
                ]);

                throw new Exception('Groq API Error');
            }

            $data = $response->json();
            return [
                'answer' => $data['choices'][0]['message']['content'] ?? '',
                'duration' => $duration,
                'model' => $model,
                'temperature' => (float)$temperature,
                'max_tokens' => (int)$maxOutputTokens,
                'system_prompt' => $systemPrompt,
                'compiled_prompt' => $prompt,
                'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                'tokens' => [
                    'prompt' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion' => $data['usage']['completion_tokens'] ?? 0,
                ]
            ];
        } catch (\Exception $e) {
            Log::emergency("Groq LLM failed to generate answer catastrophically.", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}