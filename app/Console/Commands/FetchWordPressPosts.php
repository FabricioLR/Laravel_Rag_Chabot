<?php

namespace App\Console\Commands;

use App\Jobs\IngestPost;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Ingestion;
use Carbon\Carbon;

#[Signature('app:fetch-word-press-posts')]
#[Description('Fetch new, updated or unindexed WordPress posts')]
class FetchWordPressPosts extends Command
{
    public function handle(Ingestion $ingestionService)
    {
        Log::info('WordPress post fetching pipeline initialized.');
        $startTime = microtime(true);
        
        $lastExecution = Cache::get('wp_last_execution', Carbon::now()->toDateTimeString());
        $lastIndexedPosts = Cache::get('wp_last_indexed_posts');

        if (is_null($lastIndexedPosts)) {
            Log::warning('FetchWordPressPosts: Cache key [wp_last_indexed_posts] missing. Rebuilding using Ingestion Service...');
            
            $dbIds = $ingestionService->getIndexedPostsIds();
            $lastIndexedPosts = !empty($dbIds) ? array_map('intval', $dbIds) : [0];
            
            Cache::forever('wp_last_indexed_posts', $lastIndexedPosts);
        }

        $lastIndexedPostsString = implode(',', $lastIndexedPosts);
        $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));

        try {
            $posts = DB::connection('wordpress')->select("
                SELECT 
                    p.ID, p.post_title
                FROM {$wpTablePrefix}posts p
                WHERE 
                    (p.post_status = 'publish' AND p.post_type IN ('post', 'page') AND p.post_content != '')
                    AND (
                        CASE 
                            WHEN p.post_modified_gmt > :last_execution THEN 1
                            ELSE p.ID NOT IN ({$lastIndexedPostsString})
                        END = 1
                    ) 
                LIMIT 1;
            ", [
                'last_execution' => $lastExecution
            ]);

            if (empty($posts)) {
                Log::info('No new or modified WordPress posts found in current execution window.', [
                    'last_execution_cutoff' => $lastExecution
                ]);
                return Command::SUCCESS;
            }
            
            $post = $posts[0];
            $overrideIndexing = in_array($post->ID, $lastIndexedPosts);

            if (!$overrideIndexing) {
                $lastIndexedPosts[] = $post->ID;
            }
            
            $lastExecution = Carbon::now()->toDateTimeString();
            
            Cache::put('wp_last_indexed_posts', $lastIndexedPosts);
            Cache::put('wp_last_execution', $lastExecution);

            IngestPost::dispatch([
                'id'                => $post->ID,
                'override_indexing' => $overrideIndexing
            ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('WordPress post successfully fetched and dispatched to ingestion queue.', [
                'post_id'           => $post->ID,
                'post_title'        => $post->post_title,
                'override_indexing' => $overrideIndexing,
                'duration_ms'       => $duration,
                'tracked_ids_count' => count($lastIndexedPosts) - 1,
                'new_execution_gmt' => $lastExecution
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::critical('Critical failure during WordPress post synchronization query.', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}