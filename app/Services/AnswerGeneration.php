<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class AnswerGeneration
{
    public function __construct(
        protected Embedding $embeddingService,
        protected Knowledge $knowledgeBaseService,
        protected LLM $llmService
    ) {}

    public function generate(string $userInput): string
    {
        Log::info('Chatbot pipeline started.', ['user_input' => $userInput]);
        $totalStartTime = microtime(true);

        $embeddingResult = $this->embeddingService->generate($userInput);

        $searchResult = $this->knowledgeBaseService->searchContext($userInput, $embeddingResult['vector']);

        $llmResult = $this->llmService->generateAnswer($userInput, $searchResult['context']);

        $totalDuration = round((microtime(true) - $totalStartTime) * 1000, 2);
        Log::info('Chatbot pipeline fully completed.', [
            'user_input' => $userInput,
            'total_duration_ms' => $totalDuration,
            'breakdown_ms' => [
                'embeddings' => $embeddingResult['duration'],
                'database' => $searchResult['duration'],
                'llm' => $llmResult['duration']
            ]
        ]);

        return $llmResult['answer'];
    }
}