<?php

namespace App\Services\LLM;

use App\Contracts\LLM;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenaiLLM implements LLM
{
    public function generateAnswer(string $prompt, string $systemPrompt, string $sessionId): array
    {
        try {
            $startTime = microtime(true);
            $model = config('services.openai.llm_model', env('OPENAI_LLM_MODEL', 'gpt-4o-mini'));
            $temperature = config('services.openai.llm_model_temperature', env('OPENAI_LLM_MODEL_TEMPERATURE', 0.1));
            $maxOutputTokens = config('services.openai.llm_model_max_output_tokens', env('OPENAI_LLM_MODEL_MAX_OUTPUT_TOKENS', 1024));

            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            $response = OpenAI::chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => (float)$temperature,
                'max_tokens' => (int)$maxOutputTokens,
            ]);

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $promptTokens = $response->usage->promptTokens ?? 0;
            $completionTokens = $response->usage->completionTokens ?? 0;
            $totalTokens = $response->usage->totalTokens ?? ($promptTokens + $completionTokens);

            return [
                'answer' => $response->choices[0]->message->content,
                'duration' => $durationMs,
                'model' => $model,
                'temperature' => (float)$temperature,
                'max_tokens' => (int)$maxOutputTokens,
                'system_prompt' => $systemPrompt,
                'compiled_prompt' => $prompt,
                'total_tokens' => $totalTokens,
                'tokens' => [
                    'prompt' => $promptTokens,
                    'completion' => $completionTokens,
                ]
            ];

        } catch (\Exception $e) {
            Log::emergency("OpenAI LLM failed to generate answer catastrophically.", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}