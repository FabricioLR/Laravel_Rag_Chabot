<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    public function getLatestUnindexedPosts(): array
    {
        Log::info('Fetching latest published posts that have not been indexed yet.');

        try {
            $indexedResult = DB::connection('pgvector')->select("
                SELECT DISTINCT (metadata->>'source_post_id') as post_id
                FROM vectors 
                WHERE metadata->>'source' = 'wordpress'
            ");

            $indexedPostIds = collect($indexedResult)->pluck('post_id')->filter()->toArray();

            $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));
            
            $query = "
                SELECT ID, post_title, post_date 
                FROM {$wpTablePrefix}posts 
                WHERE post_status = 'publish' 
                AND post_type IN ('post', 'page') 
                AND post_content != ''
            ";

            if (!empty($indexedPostIds)) {
                $placeholders = implode(',', array_fill(0, count($indexedPostIds), '?'));
                $query .= " AND ID NOT IN ($placeholders)";
            }

            $query .= " ORDER BY post_date DESC LIMIT 5";

            $unindexedPosts = DB::connection('wordpress')->select($query, $indexedPostIds);

            return $unindexedPosts;

        } catch (Exception $e) {
            Log::error('Failed to fetch latest unindexed posts.', [
                'exception' => get_class($e),
                'message'   => $e->getMessage()
            ]);
            
            return [];
        }
    }

    public function getLatestIndexedPosts(): array
    {
        Log::info('Fetching latest indexed posts for admin dashboard.');

        try {
            $recentVectors = DB::connection('pgvector')->select("
                SELECT * FROM (
                    SELECT DISTINCT ON (metadata->>'source_post_id') 
                        metadata->>'source_post_id' AS post_id, 
                        created_at 
                    FROM vectors 
                    ORDER BY metadata->>'source_post_id', created_at DESC
                ) subquery
                ORDER BY created_at DESC 
                LIMIT 5;
            ");

            if (empty($recentVectors)) {
                return [];
            }

            $vectorMap = [];
            foreach ($recentVectors as $vector) {
                $vectorMap[$vector->post_id] = $vector->created_at;
            }

            $postIds = array_keys($vectorMap);
            $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));
            
            $placeholders = implode(',', array_fill(0, count($postIds), '?'));

            $wpPosts = DB::connection('wordpress')->select("
                SELECT ID, post_title, post_date 
                FROM {$wpTablePrefix}posts 
                WHERE ID IN ($placeholders)
            ", $postIds);

            foreach ($wpPosts as $post) {
                $post->indexed_at = $vectorMap[$post->ID] ?? null;
            }

            return collect($wpPosts)->sortByDesc('indexed_at')->values()->all();

        } catch (Exception $e) {
            Log::error('Failed to fetch latest indexed posts.', [
                'exception' => get_class($e),
                'message'   => $e->getMessage()
            ]);
            
            return [];
        }
    }

    public function getLatestFailedJobs(): array
    {
        Log::info('DashboardMetrics: Starting execution of getLatestFailedJobs().');

        try {
            $failedJobs = DB::table('failed_jobs')
                ->where('payload', 'like', '%IngestPost%')
                ->orderBy('failed_at', 'DESC')
                ->limit(3)
                ->get();


            if ($failedJobs->isEmpty()) {
                return [];
            }

            $processedFailures = [];
            $postIds = [];

            foreach ($failedJobs as $index => $job) {
                $payload = json_decode($job->payload, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                
                $commandString = $payload['data']['command'] ?? '';

                $postId = null;
                
                if (!empty($commandString)) {
                    try {
                        $unserializedJob = unserialize($commandString);
                        if ($unserializedJob && isset($unserializedJob->postData['id'])) {
                            $postId = $unserializedJob->postData['id'];
                        }
                    } catch (\Throwable $unserializeError) {
                        Log::debug("DashboardMetrics: native unserialize() failed on job ID [{$job->id}]. Falling back to regex extraction.", [
                            'error_message' => $unserializeError->getMessage()
                        ]);
                    }

                    if (!$postId) {
                        if (preg_match('/"id";i:(\d+)/', $commandString, $matches)) {
                            $postId = (int)$matches[1];
                        }
                    }
                }

                if ($postId) {
                    $postIds[] = $postId;
                }

                $shortException = Str::limit($job->exception ?? 'Unknown error context.', 150, '...');
                $failedAt = $job->failed_at ?? now();

                $processedFailures[] = [
                    'id' => $job->id,
                    'post_id' => $postId,
                    'title' => 'Unknown Post Title', 
                    'error' => $shortException,
                    'failed_at' => $failedAt
                ];
            }

            if (!empty($postIds)) {
                $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));
                $uniquePostIds = array_values(array_unique($postIds));
                $placeholders = implode(',', array_fill(0, count($uniquePostIds), '?'));

                $wpPosts = DB::connection('wordpress')
                    ->select("SELECT ID, post_title FROM {$wpTablePrefix}posts WHERE ID IN ($placeholders)", $uniquePostIds);

                $wpTitlesMap = collect($wpPosts)->pluck('post_title', 'ID')->toArray();

                foreach ($processedFailures as &$failure) {
                    if ($failure['post_id'] && isset($wpTitlesMap[$failure['post_id']])) {
                        $failure['title'] = $wpTitlesMap[$failure['post_id']];
                    }
                }
            }

            Log::info('DashboardMetrics: Execution completed successfully.', [
                'final_failures_count' => count($processedFailures)
            ]);

            return $processedFailures;

        } catch (Exception $e) {
            Log::error('DashboardMetrics: Catastrophic lifecycle failure inside getLatestFailedJobs().', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}