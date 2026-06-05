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

        $bindings = [
            'vector1' => $vectorString,
            'vector2' => $vectorString,
            'vector3' => $vectorString,
            'input1'  => $userInput,
            'input2'  => $userInput,
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
                sum(rrf_score(searches.rank::int, 60)) AS score
            FROM (
                (
                    SELECT
                        id,
                        text,
                        metadata,
                        rank() OVER (ORDER BY :vector1::vector <=> embedding) AS rank
                    FROM vectors
                    WHERE (:vector2::vector <=> embedding) < 0.45
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
            HAVING sum(rrf_score(searches.rank::int, 60)) > 0.015
            ORDER BY score DESC
            LIMIT 5;
        ", $bindings);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if (empty($results)) {
            Log::warning('Hybrid Search returned 0 results.', [
                'duration_ms' => $duration,
                'user_input' => $userInput,
                'main_category' => $mainCategory,
                'child_category' => $childCategory
            ]);
            throw new Exception('Failed to retrieve context.');
        }

        Log::info('Hybrid search execution completed.', [
            'duration_ms' => $duration,
            'results_count' => count($results)
        ]);

        return [
            'context' => $this->formatContext($results),
            'duration' => $duration
        ];
    }


    private function formatContext(array $results): string
    {
        $context = "";
        foreach ($results as $index => $item) {
            $metadata = json_decode($item->metadata, true);
            $sourceUrl = $metadata['source_post_url'] ?? 'N/A';
            $sourceTitle = $metadata['source_post_title'] ?? 'N/A';
            
            $context .= "<" . $sourceTitle . ">\n";
            $context .= "[URL do Post]: " . $sourceUrl . "\n";
            $context .= "[Título do Post]: " . $sourceTitle . "\n";
            $context .= "[Texto do Post]: " . $item->text . "\n";
            $context .= "</" . $sourceTitle . ">\n\n";
        }
        return $context;
    }
}