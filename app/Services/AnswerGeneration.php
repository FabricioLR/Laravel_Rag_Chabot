<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\LLM\LLMManager;
use App\Services\Embedding\EmbeddingManager;
use App\Models\GenerationTelemetry;
use Exception;

class AnswerGeneration
{
    public function __construct(
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
        $embeddingService = EmbeddingManager::make();

        $conversationHistory = $this->historyService->getFormattedHistory($sessionId);

        $embeddingResult = $embeddingService->generate($userInput, "query");

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

        try {
            GenerationTelemetry::create([
                'conversation_history_id' => $conversationId,
                'model'                   => $llmResult['model'],
                'temperature'             => $llmResult['temperature'],
                'max_tokens'              => $llmResult['max_tokens'],
                'main_category'           => $mainCategory,
                'child_category'          => $childCategory,
                'system_prompt'           => $llmResult['system_prompt'],
                'compiled_prompt'         => $llmResult['compiled_prompt'],
                'prompt_tokens'           => $llmResult['tokens']['prompt'] ?? 0,
                'completion_tokens'       => $llmResult['tokens']['completion'] ?? 0,
                'total_tokens'            => $llmResult['total_tokens'] ?? 0,
                'llm_duration_ms'         => (int) $llmResult['duration'],
                'embedding_duration_ms'   => (int) ($embeddingResult['duration'] ?? 0),
                'database_duration_ms'    => (int) ($searchResult['duration'] ?? 0),
                'total_duration_ms'       => (int) $totalDuration,
            ]);
        } catch (Exception $e) {
            Log::error('Failed logging LLM generation metrics to DB: ' . $e->getMessage());
        }

        return ['answer' => $llmResult['answer'], 'conversationId' => $conversationId];
    }
}