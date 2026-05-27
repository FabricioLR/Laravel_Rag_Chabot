<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class AnswerGeneration
{
    public function __construct(
        protected Embedding $embeddingService,
        protected Knowledge $knowledgeBaseService,
        protected LLM $llmService,
        protected ConversationHistory $historyService
    ) {}

    public function generate(string $userInput, string $sessionId): string
    {
        Log::info('Chatbot pipeline started.', ['user_input' => $userInput, 'session_id' => $sessionId]);
        $totalStartTime = microtime(true);

        $conversationHistory = $this->historyService->getFormattedHistory($sessionId);

        $embeddingResult = $this->embeddingService->generate($userInput);

        $searchResult = $this->knowledgeBaseService->searchContext($userInput, $embeddingResult['vector']);

        $llmResult = $this->llmService->generateAnswer($userInput, $sessionId, $searchResult['context'], $conversationHistory);

        $this->historyService->store($sessionId, $userInput, $llmResult['answer']);

        $totalDuration = round((microtime(true) - $totalStartTime) * 1000, 2);

        $userInputLength = strlen($userInput);
        $contextLength = strlen($searchResult['context'] ?? '');
        $historyLength = strlen($conversationHistory ?? '');
        $totalPayloadLength = $userInputLength + $contextLength + $historyLength;
        
        Log::info('Chatbot pipeline fully completed.', [
            'user_input' => $userInput,
            'session_id' => $sessionId,
            'total_duration_ms' => $totalDuration,
            'breakdown_ms' => [
                'embeddings' => $embeddingResult['duration'],
                'database' => $searchResult['duration'],
                'llm' => $llmResult['duration']
            ],
            'llm_input_string_lengths' => [
                'user_query_chars'        => $userInputLength,
                'retrieved_context_chars' => $contextLength,
                'chat_history_chars'      => $historyLength,
                'combined_payload_chars'  => $totalPayloadLength,
            ]
        ]);

        return $llmResult['answer'];
    }
}