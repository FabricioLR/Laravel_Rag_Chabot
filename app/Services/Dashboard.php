<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class Dashboard
{
    public function getSyncMetrics(): array
    {
        Log::info('Fetching WordPress synchronization metrics for admin dashboard.');

        try {
            $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));

            $wpResult = DB::connection('wordpress')->select("
                SELECT COUNT(*) AS total 
                FROM {$wpTablePrefix}posts 
                WHERE post_status = 'publish' 
                AND post_type IN ('post', 'page') 
                AND post_content != ''
            ");

            $totalWpPosts = $wpResult[0]->total ?? 0;

            $vectorResult = DB::connection('pgvector')->select("
                SELECT COUNT(DISTINCT (metadata->>'source_post_id')) AS total 
                FROM vectors 
                WHERE metadata->>'source' = 'wordpress'
            ");

            $indexedPostsCount = $vectorResult[0]->total ?? 0;

            $postsLeft = max(0, $totalWpPosts - $indexedPostsCount);
            
            $progressPercentage = $totalWpPosts > 0 
                ? round(($indexedPostsCount / $totalWpPosts) * 100, 2) 
                : 0.00;

            return [
                'total_wordpress_posts' => $totalWpPosts,
                'indexed_posts_count'   => $indexedPostsCount,
                'posts_remaining'       => $postsLeft,
                'sync_progress_percent' => $progressPercentage,
            ];

        } catch (Exception $e) {
            Log::error('Failed to aggregate synchronization metrics for admin dashboard.', [
                'exception' => get_class($e),
                'message'   => $e->getMessage()
            ]);

            throw new Exception('Could not compile synchronization metrics: ' . $e->getMessage());
        }
    }

    public function getLatestIndexedPosts(): array
    {
        Log::info('Fetching latest indexed posts for admin dashboard.');

        try {
            $recentVectors = DB::connection('pgvector')->select("
                SELECT DISTINCT ON (metadata->>'source_post_id') 
                    metadata->>'source_post_id' as post_id, created_at
                FROM vectors 
                WHERE metadata->>'source' = 'wordpress'
                ORDER BY metadata->>'source_post_id', created_at DESC
                LIMIT 10
            ");

            if (empty($recentVectors)) {
                return [];
            }

            $postIds = array_map(fn($item) => $item->post_id, $recentVectors);

            $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));
            
            $placeholders = implode(',', array_fill(0, count($postIds), '?'));

            $wpPosts = DB::connection('wordpress')->select("
                SELECT ID, post_title, post_date 
                FROM {$wpTablePrefix}posts 
                WHERE ID IN ($placeholders)
                ORDER BY FIELD(ID, $placeholders) -- Keeps the pgvector order
            ", array_merge($postIds, $postIds));

            return $wpPosts;
        } catch (Exception $e) {
            Log::error('Failed to fetch latest indexed posts.', [
                'exception' => get_class($e),
                'message'   => $e->getMessage()
            ]);
            
            return [];
        }
    }
}