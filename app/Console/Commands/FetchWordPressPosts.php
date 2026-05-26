<?php

namespace App\Console\Commands;

use App\Jobs\IngestPost;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

#[Signature('app:fetch-word-press-posts')]
#[Description('Fetch new, updated or unindexed WordPress posts')]
class FetchWordPressPosts extends Command
{
    public function handle()
    {
        Log::info('Tarefa agendada: wp:fetch-posts iniciada.');
        $startTime = microtime(true);
        
        $lastExecution = Cache::get('wp_last_execution', Carbon::now()->toDateTimeString());
        $lastIndexedPosts = Cache::get('wp_last_indexed_posts', [0]);

        $lastIndexedPostsString = implode(',', $lastIndexedPosts);
        $wp_table_prefix = env('WP_DB_TABLE_PREFIX', 'wp_');

        try {
            $posts = DB::connection('wordpress')->select("
                SELECT 
                    p.ID, p.post_title
                FROM {$wp_table_prefix}posts p
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
                Log::info('Nenhum post novo ou modificado foi encontrado no WordPress neste minuto.');
                return Command::SUCCESS;
            }
            
            $post = $posts[0];
            
            $overrideIndexing = false;
            
            if (in_array($post->ID, $lastIndexedPosts)) {
                $overrideIndexing = true;
            }

            if (!$overrideIndexing) $lastIndexedPosts[] = $post->ID;
            $lastExecution = Carbon::now()->toDateTimeString();
            
            Cache::put('wp_last_indexed_posts', $lastIndexedPosts);
            Cache::put('wp_last_execution', $lastExecution);

            IngestPost::dispatch([
                'id'         => $post->ID,
                'override_indexing' => $overrideIndexing
            ]);

            Log::info("Post ID {$post->ID} [{$post->post_title}] enviado para a fila de ingestão.");
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Sincronização do post realizada com sucesso.', [
                'post_id'          => $post->ID,
                'last_execution'   => Cache::get('wp_last_execution'),
                'indexed_count'    => count($lastIndexedPosts) - 1,
                'duration_ms'      => $duration
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Erro crítico ao executar query de sincronização do WordPress.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}