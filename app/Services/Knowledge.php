<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class Knowledge
{
    public function searchContext(string $userInput, array $vectorArray, ?string $mainCategory = null, ?string $childCategory = null): array {
        $startTime = microtime(true);
        $vectorString = '[' . implode(',', $vectorArray) . ']';

        $maxVectorDistance = config("rag.search.max_distance", env("RAG_MAX_VECTOR_DISTANCE", 0.45));
        $maxRetrievalChunks = config("rag.search.max_chunks", env("RAG_MAX_RETRIEVAL_CHUNKS", 4));
        $minimumRffScore = config("rag.search.min_rff_score", env("RAG_MIN_RFF_SCORE", 0.015));
        $rffKValue = config("rag.search.rff_k", env("RAG_RFF_K_VALUE", 60));

        $bindings = [
            'vector1' => $vectorString,
            'vector2' => $vectorString,
            'vector3' => $vectorString,
            'input1'  => $userInput,
            'input2'  => $userInput,
            'vectorDistance' => $maxVectorDistance,
            'chunks' => $maxRetrievalChunks,
            'rffScore' => $minimumRffScore,
            'rrfK1' => $rffKValue,
            'rrfK2' => $rffKValue,
        ];

        $categoryFilter = '';

        if ($childCategory) {
            $childCode = trim(explode('-', $childCategory)[0]);
            
            $categoryFilter .= " AND (metadata->>'source_post_categories' LIKE :childDot OR metadata->>'source_post_categories' LIKE :childSpace)";
            $bindings['childDot'] = $childCode . '.%';
            $bindings['childSpace'] = $childCode . ' %';

        } elseif ($mainCategory) {
            $mainCode = trim(explode('-', $mainCategory)[0]);
            
            $categoryFilter .= " AND (metadata->>'source_post_categories' LIKE :mainDot OR metadata->>'source_post_categories' LIKE :mainSpace)";
            $bindings['mainDot'] = $mainCode . '.%';
            $bindings['mainSpace'] = $mainCode . ' %';
        }

        $results = DB::connection('pgvector')->select("
            SELECT
                searches.id,
                searches.text,
                searches.metadata,
                sum(rrf_score(searches.rank::int, :rrfK1)) AS score
            FROM (
                (
                    SELECT
                        id,
                        text,
                        metadata,
                        rank() OVER (ORDER BY :vector1::vector <=> embedding) AS rank
                    FROM vectors
                    WHERE (:vector2::vector <=> embedding) < :vectorDistance
                    {$categoryFilter}
                    ORDER BY :vector3::vector <=> embedding
                    LIMIT 40
                )
                UNION ALL
                (
                    SELECT
                        id,
                        text,
                        metadata,
                        rank() OVER (ORDER BY ts_rank_cd(to_tsvector('portuguese', text), plainto_tsquery('portuguese', :input1)) DESC) AS rank
                    FROM vectors
                    WHERE
                        plainto_tsquery('portuguese', :input2) @@ to_tsvector('portuguese', text)
                        {$categoryFilter}
                    ORDER BY rank
                    LIMIT 40
                )
            ) searches
            GROUP BY searches.id, searches.text, searches.metadata
            HAVING sum(rrf_score(searches.rank::int, :rrfK2)) > :rffScore
            ORDER BY score DESC
            LIMIT :chunks;
        ", $bindings);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if (empty($results)) {
            Log::warning('Hybrid Search returned 0 results.', [
                'duration_ms' => $duration,
                'user_input' => $userInput,
                'main_category' => $mainCategory,
                'child_category' => $childCategory
            ]);
            throw new Exception('No context found for this input.');
        }

        Log::info('Hybrid search execution completed.', [
            'duration_ms' => $duration,
            'results_count' => count($results)
        ]);

        $expandedContext = $this->buildWindowContext($results);

        return [
            'context' => $expandedContext,
            'duration' => $duration
        ];
    }


    private function buildWindowContext(array $results): string
    {
        $charsPerToken = config("rag.context.chars_per_token", env("RAG_CHARS_PER_TOKEN", 2.7));
        $maxContextTokens = config("rag.context.max_tokens", env("RAG_MAX_CONTEXT_TOKENS", 6000));
        
        $contextBlocks = [];
        $currentTokensCount = 0;

        foreach ($results as $item) {
            $metadata = json_decode($item->metadata, true);
            $postId = $metadata['source_post_id'] ?? null;
            $chunkIndex = isset($metadata['chunk_index']) ? (int)$metadata['chunk_index'] : null;
            $title = $metadata['source_post_title'] ?? 'N/A';
            $url = $metadata['source_post_url'] ?? 'N/A';

            $groupKey = $postId ?: 'fallback_' . $item->id;

            $currentTokensCount = $this->calculateTotalTokens($contextBlocks, $charsPerToken);
            if ($currentTokensCount >= $maxContextTokens) {
                break;
            }

            if (!isset($contextBlocks[$groupKey])) {
                $contextBlocks[$groupKey] = [
                    'title' => $title,
                    'url' => $url,
                    'original_indices' => is_null($chunkIndex) ? [] : [$chunkIndex],
                    'chunks' => [
                        (is_null($chunkIndex) ? 0 : $chunkIndex) => $item->text
                    ]
                ];
            } else {
                $targetKey = is_null($chunkIndex) ? 0 : $chunkIndex;
                $contextBlocks[$groupKey]['chunks'][$targetKey] = $item->text;
                
                if (!is_null($chunkIndex) && !in_array($chunkIndex, $contextBlocks[$groupKey]['original_indices'])) {
                    $contextBlocks[$groupKey]['original_indices'][] = $chunkIndex;
                }
            }
        }

        $currentTokensCount = $this->calculateTotalTokens($contextBlocks, $charsPerToken);

        $expansionOffsets = [-1, 1];

        if ($currentTokensCount < $maxContextTokens) {
            foreach ($expansionOffsets as $offset) {
                foreach ($contextBlocks as $postId => &$block) {
                    
                    foreach ($block['original_indices'] as $baseIndex) {
                        if (is_null($baseIndex)) {
                            continue;
                        }

                        $targetIndex = $baseIndex + $offset;

                        if (isset($block['chunks'][$targetIndex])) {
                            continue;
                        }

                        $currentTokensCount = $this->calculateTotalTokens($contextBlocks, $charsPerToken);
                        if ($currentTokensCount >= $maxContextTokens) {
                            break 3;
                        }

                        $neighborText = DB::connection('pgvector')
                            ->table('vectors')
                            ->where(DB::raw("metadata->>'source_post_id'"), (string)$postId)
                            ->where(DB::raw("cast(metadata->>'chunk_index' as integer)"), $targetIndex)
                            ->value('text');

                        if ($neighborText) {
                            $block['chunks'][$targetIndex] = $neighborText;

                            $newTokensCount = $this->calculateTotalTokens($contextBlocks, $charsPerToken);

                            if ($newTokensCount > $maxContextTokens) {
                                unset($block['chunks'][$targetIndex]);
                                break 3;
                            }
                        }
                    }
                    unset($block);
                }
            }
        }

        $finalContextString = "";
        foreach ($contextBlocks as $block_) {
            ksort($block_['chunks']);
            
            $cohesiveText = implode("\n\n", $block_['chunks']);
            $finalContextString .= $this->renderTag($block_['title'], $block_['url'], $cohesiveText);
        }

        return $finalContextString;
    }

    private function calculateTotalTokens(array $contextBlocks, float $charsPerToken): int
    {
        $tempContext = "";
        foreach ($contextBlocks as $block) {
            ksort($block['chunks']);
            $cohesiveText = implode("\n\n", $block['chunks']);
            $tempContext .= $this->renderTag($block['title'], $block['url'], $cohesiveText);
        }
        
        return (int) ceil(mb_strlen($tempContext) / $charsPerToken);
    }

    private function renderTag(string $title, string $url, string $text): string
    {
        $tag = "<" . $title . ">\n";
        $tag .= "[URL do Post]: " . $url . "\n";
        $tag .= "[Título do Post]: " . $title . "\n";
        $tag .= "[Texto do Post]: " . $text . "\n";
        $tag .= "</" . $title . ">\n\n";
        return $tag;
    }

}