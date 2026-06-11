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

        $expandedContext = $this->buildWindowContext($results);

        return [
            'context' => $expandedContext,
            'duration' => $duration
        ];
    }


    private function buildWindowContext(array $results): string
    {
        $context = "";
        $processedPosts = [];

        foreach ($results as $item) {
            $metadata = json_decode($item->metadata, true);
            $postId = $metadata['source_post_id'] ?? null;
            $currentIndex = isset($metadata['chunk_index']) ? (int)$metadata['chunk_index'] : null;

            if (!$postId || is_null($currentIndex)) {
                $context .= $this->renderTag($metadata['source_post_title'] ?? 'N/A', $metadata['source_post_url'] ?? 'N/A', $item->text);
                continue;
            }

            if (in_array($postId, $processedPosts)) {
                continue;
            }
            $processedPosts[] = $postId;

            $windowChunks = DB::connection('pgvector')
                ->table('vectors')
                ->where(DB::raw("metadata->>'source_post_id'"), (string)$postId)
                ->whereIn(DB::raw("cast(metadata->>'chunk_index' as integer)"), [
                    $currentIndex - 1, 
                    $currentIndex, 
                    $currentIndex + 1
                ])
                ->orderBy(DB::raw("cast(metadata->>'chunk_index' as integer)"))
                ->pluck('text')
                ->toArray();

            $cohesiveText = implode("\n\n", $windowChunks);

            $context .= $this->renderTag(
                $metadata['source_post_title'] ?? 'N/A',
                $metadata['source_post_url'] ?? 'N/A',
                $cohesiveText
            );
        }

        return $context;
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