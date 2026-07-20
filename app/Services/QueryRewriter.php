<?php

namespace App\Services;

use App\Services\LLM\LLMManager;
use Illuminate\Support\Facades\Log;

class QueryRewriter
{
    public function __construct(
        protected LLMManager $llmManager,
        protected PromptBuilder $promptBuilder
    ) {}

    public function rewrite(string $sessionId, string $userInput, string $conversationHistory): array
    {
        //if (empty(trim($conversationHistory))) {
        //    return [
        //        'query' => $userInput,
        //        'llm'   => null,
        //    ];
        //}

        [$systemPrompt, $prompt] = $this->promptBuilder->buildQueryRewriterPrompt($userInput, $conversationHistory);

        try {
            $llmResult = $this->llmManager->make()->generateAnswer($prompt, $systemPrompt, $sessionId);
            $rewritten = trim($llmResult['answer'] ?? '');

            Log::info('Query successfully rewritten for vector search.', [
                'original'  => $userInput,
                'rewritten' => $rewritten,
            ]);

            return [
                'query' => !empty($rewritten) ? $rewritten : $userInput,
                'llm'   => $llmResult,
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to rewrite query. Falling back to raw input.', [
                'error' => $e->getMessage()
            ]);

            return [
                'query' => $userInput,
                'llm'   => null,
            ];
        }
    }
}