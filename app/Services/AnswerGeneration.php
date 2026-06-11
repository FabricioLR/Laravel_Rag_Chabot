<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\LLM\LLMManager;
use Exception;

class AnswerGeneration
{
    public function __construct(
        protected Embedding $embeddingService,
        protected Knowledge $knowledgeBaseService,
        protected ConversationHistory $historyService
    ) {}

    public function generate(string $userInput, string $sessionId, ?string $mainCategory = null, ?string $childCategory = null): array
    {
        Log::info('Chatbot pipeline started.', [
            'user_input'     => $userInput, 
            'session_id'     => $sessionId,
            'main_category'  => $mainCategory,
            'child_category' => $childCategory
        ]);
        $totalStartTime = microtime(true);

        $llm = LLMManager::make();

        $conversationHistory = $this->historyService->getFormattedHistory($sessionId);

        $embeddingResult = $this->embeddingService->generate($userInput);

        $searchResult = $this->knowledgeBaseService->searchContext($userInput, $embeddingResult['vector'], $mainCategory, $childCategory);

        $llmResult = $llm->generateAnswer($userInput, $sessionId, $searchResult['context'], $conversationHistory);

        $conversationId = $this->historyService->store($sessionId, $userInput, $llmResult['answer']);

        $totalDuration = round((microtime(true) - $totalStartTime) * 1000, 2);

        $userInputLength = strlen($userInput);
        $contextLength = strlen($searchResult['context'] ?? '');
        $historyLength = strlen($conversationHistory ?? '');
        $totalPayloadLength = $userInputLength + $contextLength + $historyLength;
        
        Log::info('Chatbot pipeline fully completed.', [
            'question' => $userInput,
            'answer' => $llmResult['answer'],
            'session_id' => $sessionId,
            'total_duration_ms' => $totalDuration,
            'breakdown_ms' => [
                'embeddings' => $embeddingResult['duration'],
                'database' => $searchResult['duration'],
                'llm' => $llmResult['duration']
            ],
            'total_payload_chars' => $totalPayloadLength,
            'llm_input_string_lengths' => [
                'user_query_chars'        => $userInputLength,
                'retrieved_context_chars' => $contextLength,
                'chat_history_chars'      => $historyLength,
            ],
            'total_tokens' => $llmResult['total_tokens']
        ]);

        return ['answer' => $llmResult['answer'], 'conversationId' => $conversationId];
    }
}