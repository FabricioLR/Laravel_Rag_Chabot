<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\LLM\LLMManager;
use App\Services\Embedding\EmbeddingManager;
use App\Models\GenerationTelemetry;

class PipelineService
{
    public function __construct(
        protected HybridSearchService $hybridSearchService,
        protected ConversationHistory $historyService,
        protected Category $categoryService,
        protected LLMManager $llmManager,
        protected EmbeddingManager $embeddingManager,
        protected PromptBuilder $promptBuilder,
        protected QueryRewriter $queryRewriter,
        protected RerankerService $rerankerService
    ) {}

    public function generate(string $query, string $sessionId, string $origin, ?string $mainCategory = null, ?string $childCategory = null): array
    {
        $totalStartTime = microtime(true);

        $conversationHistory = $this->historyService->getFormattedHistory($sessionId);

        $rewriteResult  = $this->queryRewriter->rewrite($sessionId, $query, $conversationHistory);
        $rewriteQuery = $rewriteResult['query'];
        $rewriteTelemetry = $rewriteResult['telemetry'];

        $embeddingResult = $this->embeddingManager->make()->generate($rewriteQuery, "query");
        $embeddingVector = $embeddingResult['vector'];
        $embeddingTelemetry = $embeddingResult['telemetry'];
        
        $hybridSearchResult = $this->hybridSearchService->retrieveFormatedContext(
            $rewriteQuery, 
            $embeddingResult['vector'], 
            $mainCategory, 
            $childCategory
        );
        $hybridSearchContext = $hybridSearchResult['context'];
        $hybridSearchTelemetry = $hybridSearchResult['telemetry'];

        $rerankResult = $this->rerankerService->rerank($rewriteQuery, $hybridSearchContext, 5);
        $rerankDocuments = $rerankResult['documents'];
        $rerankTelemetry = $rerankResult['telemetry'];
        
        $context = implode('', $rerankDocuments);

        [$systemPrompt, $prompt] = $this->promptBuilder->buildStandardPrompt($rewriteQuery, $context, $conversationHistory);

        $llmResult = $this->llmManager->make()->generateAnswer($prompt, $systemPrompt, $sessionId);
        $llmAnswer = $llmResult['answer'];
        $llmTelemetry = $llmResult['telemetry'];

        $conversationId = $this->historyService->store($sessionId, $query, $llmAnswer);

        $totalDuration = round((microtime(true) - $totalStartTime) * 1000, 2);

        $this->logPerformanceMetrics(
            $query, $rewriteQuery, $sessionId, $conversationId, $origin,
            $mainCategory, $childCategory,
            $rewriteTelemetry,
            $embeddingTelemetry,
            $hybridSearchTelemetry,
            $rerankTelemetry,
            $llmTelemetry, $llmAnswer,
            $totalDuration
        );
        $this->saveTelemetry(
            $query, $rewriteQuery, $sessionId, $conversationId, $origin,
            $mainCategory, $childCategory,
            $rewriteTelemetry,
            $embeddingTelemetry,
            $hybridSearchTelemetry,
            $rerankTelemetry,
            $context,
            $llmTelemetry, $llmAnswer,
            $totalDuration
        );

        return [ 
            'answer' => $llmAnswer, 
            'conversationId' => $conversationId
        ];
    }
    private function logPerformanceMetrics(
        string $query, string $rewriteQuery, string $sessionId, string $conversationId, string $origin,
        ?string $mainCategory = null, ?string $childCategory = null,
        ?array $rewriteTelemetry = [],
        array $embeddingTelemetry,
        array $hybridSearchTelemetry,
        ?array $rerankTelemetry = [],
        array $llmTelemetry, string $llmAnswer,
        float $totalDuration
    ): void {
        Log::info('Chatbot pipeline fully completed.', [
            'user_input'        => $query,
            'rewritten_query'   => $rewriteQuery,
            'origin'            => $origin,
            'answer'            => $llmAnswer,
            'session_id'        => $sessionId,
            'conversation_id'        => $conversationId,
            'total_duration_ms' => $totalDuration,
            'breakdown_ms'      => [
                'query_rewriter' => $rewriteTelemetry['duration'] ?? 0,
                'embeddings'         => $embeddingTelemetry['duration'] ?? 0,
                'hybrid_search'           => $hybridSearchTelemetry['duration'] ?? 0,
                'reranker'           => $rerankTelemetry['duration'] ?? 0,
                'main_llm'           => $llmTelemetry['duration'] ?? 0,
            ],
            'token_metrics' => [
                'query_rewriter' => [
                    'prompt_tokens'     => $rewriteTelemetry['tokens']['prompt'] ?? 0,
                    'completion_tokens' => $rewriteTelemetry['tokens']['completion'] ?? 0,
                    'total_tokens'      => $rewriteTelemetry['total_tokens'] ?? 0,
                ],
                'reranker' => [
                    'total_tokens'      => $rerankTelemetry['total_tokens'] ?? 0,
                ],
                'main_llm' => [
                    'prompt_tokens'     => $llmTelemetry['tokens']['prompt'] ?? 0,
                    'completion_tokens' => $llmTelemetry['tokens']['completion'] ?? 0,
                    'total_tokens'      => $llmTelemetry['total_tokens'] ?? 0,
                ],
            ],
        ]);
    }
    private function saveTelemetry(
        string $query, string $rewriteQuery, string $sessionId, string $conversationId, string $origin,
        ?string $mainCategory = null, ?string $childCategory = null,
        ?array $rewriteTelemetry = [],
        array $embeddingTelemetry,
        array $hybridSearchTelemetry,
        ?array $rerankTelemetry = [],
        string $context,
        array $llmTelemetry, string $llmAnswer,
        float $totalDuration,
    ): void {
        try {
            $parsed = parse_url($origin);
            $domainWithPort = $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
            GenerationTelemetry::create([
                'conversation_history_id' => $conversationId,
                'origin'                  => $domainWithPort,
                'user_input'              => $query,
                'rewritten_query'         => $rewriteQuery,

                'rewrite_prompt_tokens'     => $rewriteTelemetry['tokens']['prompt'] ?? null,
                'rewrite_completion_tokens' => $rewriteTelemetry['tokens']['completion'] ?? null,
                'rewrite_total_tokens'      => $rewriteTelemetry['total_tokens'] ?? null,
                'rewrite_duration_ms'       => (int) ($rewriteTelemetry['duration'] ?? 0),

                'rerank_total_tokens' => $rerankTelemetry['total_tokens'] ?? null,
                'rerank_duration_ms' => (int) ($rerankTelemetry['duration'] ?? 0),

                'model'           => $llmTelemetry['model'] ?? null,
                'temperature'     => $llmTelemetry['temperature'] ?? null,
                'max_tokens'      => $llmTelemetry['max_tokens'] ?? null,
                'main_category'   => $mainCategory,
                'child_category'  => $childCategory,
                'system_prompt'   => $llmTelemetry['system_prompt'] ?? null,
                'compiled_prompt' => $llmTelemetry['compiled_prompt'] ?? null,
                'prompt_tokens'   => $llmTelemetry['tokens']['prompt'] ?? 0,
                'completion_tokens' => $llmTelemetry['tokens']['completion'] ?? 0,
                'total_tokens'    => $llmTelemetry['total_tokens'] ?? 0,

                'llm_duration_ms'       => (int) ($llmTelemetry['duration'] ?? 0),
                'embedding_duration_ms' => (int) ($embeddingTelemetry['duration'] ?? 0),
                'database_duration_ms'  => (int) ($hybridSearchTelemetry['duration'] ?? 0),
                'total_duration_ms'     => (int) $totalDuration,
            ]);
        } catch (Exception $e) {
            Log::error('Failed logging LLM generation metrics to DB: ' . $e->getMessage());
        }
    }
}