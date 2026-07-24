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

        $categoryFilter1 = '';
        $categoryFilter2 = '';
        $selectedCategory = $childCategory ?? $mainCategory;

        if (!empty($selectedCategory)) {
            $rawCategories = explode(',', $selectedCategory);
            $codes = [];

            foreach ($rawCategories as $cat) {
                $cat = trim($cat);
                if ($cat === '') continue;

                if (preg_match('/^([0-9]+(?:[\.-][0-9]+)*)/', $cat, $matches)) {
                    $code = str_replace('-', '.', $matches[1]);
                    $codes[] = preg_quote($code, '/');
                }
            }

            if (!empty($codes)) {
                $pattern = '(^|, )(' . implode('|', array_unique($codes)) . ')(\.|\s|$)';

                $categoryFilter1 = " AND metadata->>'source_post_categories' ~ :categoryRegex1";
                $categoryFilter2 = " AND metadata->>'source_post_categories' ~ :categoryRegex2";

                $bindings['categoryRegex1'] = $pattern;
                $bindings['categoryRegex2'] = $pattern;
            }
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
                    {$categoryFilter1}
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
                        {$categoryFilter2}
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

            if ($mainCategory == null && $childCategory == null){
                return [
                    'context' => "Nada relacionado foi encontrado.",
                    'duration' => $duration
                ];    
            }
            
            return [
                'context' => "",
                'duration' => $duration
            ];
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
        $charsPerToken = config('rag.context.chars_per_token', env('RAG_CHARS_PER_TOKEN', 2.7));
        $maxContextTokens = config('rag.context.max_tokens', env('RAG_MAX_CONTEXT_TOKENS', 6000));
        $maxChars = (int) ($maxContextTokens * $charsPerToken);

        if (empty($results)) {
            return '';
        }

        $contextBlocks = [];
        $currentChars = 0;

        foreach ($results as $item) {
            $metadata = is_string($item->metadata) ? json_decode($item->metadata, true) : (array) $item->metadata;
            $postId = $metadata['source_post_id'] ?? ('fallback_' . $item->id);
            $chunkIndex = isset($metadata['chunk_index']) ? (int) $metadata['chunk_index'] : 0;

            if (!isset($contextBlocks[$postId])) {
                $contextBlocks[$postId] = [
                    'title' => $metadata['source_post_title'] ?? 'N/A',
                    'url'   => $metadata['source_post_url'] ?? 'N/A',
                    'original_indices' => [],
                    'chunks' => []
                ];
            }

            if (!isset($contextBlocks[$postId]['chunks'][$chunkIndex])) {
                $contextBlocks[$postId]['chunks'][$chunkIndex] = $item->text;
                $contextBlocks[$postId]['original_indices'][] = $chunkIndex;
            }
        }

        $currentChars = $this->calculateTotalChars($contextBlocks);
        if ($currentChars >= $maxChars) {
            return $this->formatFinalContext($contextBlocks, $maxChars);
        }

        $missingRequests = [];
        foreach ($contextBlocks as $postId => $block) {
            if (str_starts_with((string)$postId, 'fallback_')) {
                continue;
            }

            foreach ($block['original_indices'] as $baseIdx) {
                foreach ([-1, 1] as $offset) {
                    $targetIdx = $baseIdx + $offset;
                    if ($targetIdx >= 0 && !isset($block['chunks'][$targetIdx])) {
                        $missingRequests[$postId][] = $targetIdx;
                    }
                }
            }
        }

        if (!empty($missingRequests)) {
            $queryBuilder = DB::connection('pgvector')->table('vectors');

            $queryBuilder->where(function ($query) use ($missingRequests) {
                foreach ($missingRequests as $postId => $indices) {
                    $indices = array_unique($indices);
                    $query->orWhere(function ($q) use ($postId, $indices) {
                        $q->where(DB::raw("metadata->>'source_post_id'"), (string)$postId)
                        ->whereIn(DB::raw("cast(metadata->>'chunk_index' as integer)"), $indices);
                    });
                }
            });

            $neighbors = $queryBuilder->select('text', 'metadata')->get();

            foreach ($neighbors as $neighbor) {
                $meta = is_string($neighbor->metadata) ? json_decode($neighbor->metadata, true) : (array) $neighbor->metadata;
                $postId = $meta['source_post_id'] ?? null;
                $chunkIndex = isset($meta['chunk_index']) ? (int) $meta['chunk_index'] : null;

                if ($postId && !is_null($chunkIndex) && isset($contextBlocks[$postId])) {
                    $contextBlocks[$postId]['chunks'][$chunkIndex] = $neighbor->text;

                    if ($this->calculateTotalChars($contextBlocks) > $maxChars) {
                        unset($contextBlocks[$postId]['chunks'][$chunkIndex]);
                        break;
                    }
                }
            }
        }

        $result = $this->formatFinalContext($contextBlocks, $maxChars);

        return $result;
    }
    private function calculateTotalChars(array $contextBlocks): int
    {
        $totalChars = 0;
        foreach ($contextBlocks as $block) {
            ksort($block['chunks']);
            $cohesiveText = implode("\n\n", $block['chunks']);
            $totalChars += mb_strlen($this->renderTag($block['title'], $block['url'], $cohesiveText));
        }

        return $totalChars;
    }
    private function formatFinalContext(array $contextBlocks, int $maxChars): string
    {
        $out = '';
        foreach ($contextBlocks as $block) {
            ksort($block['chunks']);
            $cohesiveText = implode("\n\n", $block['chunks']);
            $rendered = $this->renderTag($block['title'], $block['url'], $cohesiveText);

            if (mb_strlen($out . $rendered) > $maxChars) {
                break;
            }

            $out .= $rendered;
        }

        return $out;
    }
    private function renderTag(string $title, string $url, string $text): string
    {
        return "<{$title}>\n"
            . "[URL do Post]: {$url}\n"
            . "[Título do Post]: {$title}\n"
            . "[Texto do Post]: {$text}\n"
            . "</{$title}>\n\n";
    }

}