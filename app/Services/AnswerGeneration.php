<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\LLM\LLMManager;
use App\Services\Embedding\EmbeddingManager;
use App\Models\GenerationTelemetry;

class AnswerGeneration
{
    public function __construct(
        protected Knowledge $knowledgeBaseService,
        protected ConversationHistory $historyService,
        protected Category $categoryService,
        protected LLMManager $llmManager,
        protected EmbeddingManager $embeddingManager,
        protected PromptBuilder $promptBuilder,
        protected QueryRewriter $queryRewriter
    ) {}

    public function generate(string $userInput, string $sessionId, ?string $mainCategory = null, ?string $childCategory = null): array
    {
        Log::info('Chatbot pipeline started.', compact('userInput', 'sessionId', 'mainCategory', 'childCategory'));
        $totalStartTime = microtime(true);

        $conversationHistory = $this->historyService->getFormattedHistory($sessionId);
        $rewriteResult  = $this->queryRewriter->rewrite($sessionId, $userInput, $conversationHistory);
        $searchQuery    = $rewriteResult['query'];
        $rewriteLlm     = $rewriteResult['llm'];
        $embeddingResult = $this->embeddingManager->make()->generate($searchQuery, "query");
        
        $searchResult = $this->knowledgeBaseService->searchContext(
            $searchQuery, 
            $embeddingResult['vector'], 
            $mainCategory, 
            $childCategory
        );

        if (empty($searchResult['context'])) {
            $formattedCategories = $this->categoryService->getFormatedChildCategories($mainCategory, $childCategory);
            [$systemPrompt, $prompt] = $this->promptBuilder->buildFallbackPrompt($searchQuery, $formattedCategories);
        } else {
            [$systemPrompt, $prompt] = $this->promptBuilder->buildStandardPrompt($searchQuery, $searchResult['context'], $conversationHistory);
        }

        Log::debug("Full string context compiled for LLM request.", [
            'session_id' => $sessionId,
            'full_user_prompt' => $prompt
        ]);

        $llmResult = $this->llmManager->make()->generateAnswer($prompt, $systemPrompt, $sessionId);
        $conversationId = $this->historyService->store($sessionId, $userInput, $llmResult['answer']);

        $totalDuration = round((microtime(true) - $totalStartTime) * 1000, 2);
        $this->logPerformanceMetrics($userInput, $searchQuery, $conversationHistory, $searchResult, $llmResult, $rewriteLlm, $totalDuration, $sessionId);
        $this->saveTelemetry($conversationId, $userInput, $searchQuery, $llmResult, $rewriteLlm, $embeddingResult, $searchResult, $totalDuration, $mainCategory, $childCategory);

        return [
            'answer' => $llmResult['answer'], 
            'conversationId' => $conversationId
        ];
    }
    private function logPerformanceMetrics(
        string $rawUserInput, 
        string $rewrittenQuery, 
        string $history, 
        array $search, 
        array $llm, 
        ?array $rewriteLlm, 
        float $duration, 
        string $sessionId
    ): void {
        $queryLen   = strlen($rewrittenQuery);
        $contextLen = strlen($search['context'] ?? '');
        $historyLen = strlen($history);

        Log::info('Chatbot pipeline fully completed.', [
            'user_input'        => $rawUserInput,
            'rewritten_query'   => $rewrittenQuery,
            'answer'            => $llm['answer'],
            'session_id'        => $sessionId,
            'total_duration_ms' => $duration,
            'breakdown_ms'      => [
                'query_rewriter_llm' => $rewriteLlm['duration'] ?? 0,
                'embeddings'         => $embeddingResult['duration'] ?? 0,
                'database'           => $search['duration'] ?? 0,
                'main_llm'           => $llm['duration'] ?? 0,
            ],
            'total_payload_chars'      => $queryLen + $contextLen + $historyLen,
            'llm_input_string_lengths' => [
                'user_query_chars'        => $queryLen,
                'retrieved_context_chars' => $contextLen,
                'chat_history_chars'      => $historyLen,
            ],
            'token_metrics' => [
                'query_rewriter' => [
                    'prompt_tokens'     => $rewriteLlm['tokens']['prompt'] ?? 0,
                    'completion_tokens' => $rewriteLlm['tokens']['completion'] ?? 0,
                    'total_tokens'      => $rewriteLlm['total_tokens'] ?? 0,
                ],
                'main_llm' => [
                    'prompt_tokens'     => $llm['tokens']['prompt'] ?? 0,
                    'completion_tokens' => $llm['tokens']['completion'] ?? 0,
                    'total_tokens'      => $llm['total_tokens'] ?? 0,
                ],
            ],
        ]);
    }

    private function saveTelemetry(
        $conversationId, 
        string $rawUserInput, 
        string $rewrittenQuery, 
        array $llm, 
        ?array $rewriteLlm, 
        array $embedding, 
        array $search, 
        float $totalDuration, 
        ?string $mainCat, 
        ?string $childCat
    ): void {
        try {
            GenerationTelemetry::create([
                'conversation_history_id' => $conversationId,
                'user_input'              => $rawUserInput,
                'rewritten_query'         => $rewrittenQuery,

                'rewrite_prompt_tokens'     => $rewriteLlm['tokens']['prompt'] ?? null,
                'rewrite_completion_tokens' => $rewriteLlm['tokens']['completion'] ?? null,
                'rewrite_total_tokens'      => $rewriteLlm['total_tokens'] ?? null,
                'rewrite_duration_ms'       => isset($rewriteLlm['duration']) ? (int) $rewriteLlm['duration'] : null,

                'model'           => $llm['model'] ?? null,
                'temperature'     => $llm['temperature'] ?? null,
                'max_tokens'      => $llm['max_tokens'] ?? null,
                'main_category'   => $mainCat,
                'child_category'  => $childCat,
                'system_prompt'   => $llm['system_prompt'] ?? null,
                'compiled_prompt' => $llm['compiled_prompt'] ?? null,
                'prompt_tokens'   => $llm['tokens']['prompt'] ?? 0,
                'completion_tokens' => $llm['tokens']['completion'] ?? 0,
                'total_tokens'    => $llm['total_tokens'] ?? 0,

                'llm_duration_ms'       => (int) ($llm['duration'] ?? 0),
                'embedding_duration_ms' => (int) ($embedding['duration'] ?? 0),
                'database_duration_ms'  => (int) ($search['duration'] ?? 0),
                'total_duration_ms'     => (int) $totalDuration,
            ]);
        } catch (Exception $e) {
            Log::error('Failed logging LLM generation metrics to DB: ' . $e->getMessage());
        }
    }
}