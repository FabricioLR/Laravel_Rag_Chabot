<?php

namespace App\Jobs;

use App\Services\TextSplitter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Support\Facades\Cache;
use App\Services\Embedding\EmbeddingManager;
use App\Services\Ingestion;
use Throwable; 
use Exception;

class IngestPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $postData;

    public function __construct(array $postData)
    {
        $this->postData = $postData;
    }

    public function failed(Throwable $exception): void
    {
        $postId = $this->postData['id'] ?? null;
        $lastIndexedPosts = Cache::get('wp_last_indexed_posts');

        if (is_null($lastIndexedPosts)) {
            try {
                $ingestionService = app(Ingestion::class); 
                $dbIds = $ingestionService->getIndexedPostsIds();
                $lastIndexedPosts = !empty($dbIds) ? array_map('intval', $dbIds) : [0];
            } catch (\Exception $e) {
                $lastIndexedPosts = [0];
            }
        }
        $result = array_diff($lastIndexedPosts, [$postId]);
        Cache::put('wp_last_indexed_posts', $result);

        Log::error('WordPress post ingestion job failed catastrophically.', [
            'post_id'   => $postId,
            'exception' => get_class($exception),
            'message'   => $exception->getMessage()
        ]);
    }

    public function handle(EmbeddingManager $embeddingManager): void
    {
        $postId = $this->postData['id'];
        $overrideIndexing = $this->postData['override_indexing'] ?? false;

        try {
            $embeddingService = $embeddingManager->make();

            if ($overrideIndexing) {
                Log::info('Override indexing activated. Purging existing vector entries for post.', [
                    'post_id' => $postId
                ]);

                DB::connection('pgvector')->statement("DELETE FROM vectors WHERE metadata->>'source_post_id' = :postId", [
                    'postId' => (string)$postId
                ]);
            }

            Log::info('Starting ingestion processing for WordPress post data.', ['post_id' => $postId]);

            $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));

            $posts = DB::connection('wordpress')->select("
                SELECT 
                    p.post_title, p.guid, p.post_content,
                    (
                        SELECT GROUP_CONCAT(t.name SEPARATOR ', ')
                        FROM {$wpTablePrefix}term_relationships r
                        INNER JOIN {$wpTablePrefix}term_taxonomy tt ON r.term_taxonomy_id = tt.term_taxonomy_id
                        INNER JOIN {$wpTablePrefix}terms t ON tt.term_id = t.term_id
                        WHERE r.object_id = p.ID 
                        AND tt.taxonomy = 'category'
                    ) AS categories
                FROM {$wpTablePrefix}posts p
                WHERE 
                    p.ID = :postId
                LIMIT 1;
            ", [
                'postId' => $postId
            ]);

            if (empty($posts)) {
                throw new Exception("Target post record was not found in the WordPress database.");
            }
            
            $post = $posts[0];

            if (!$post || empty(trim($post->post_content))) {
                throw new Exception("Post target record content payload is completely empty.");
            }

            $converter = new HtmlConverter([
                'strip_tags' => true,        
                'hard_break' => true,        
                'italic_style' => '_',       
                'bold_style' => '**',       
            ]);

            $markdownContent = $converter->convert($post->post_content);
            $chunks = TextSplitter::split($markdownContent, maxWords: 500, overlapWords: 50);

            foreach ($chunks as $index => $chunkText) {
                $embeddingResult = $embeddingService->generate($chunkText, 'passage');
                
                $vectorArray = $embeddingResult['vector'];
                $vectorString = '[' . implode(',', $vectorArray) . ']';

                $metadata = json_encode([
                    'source' => 'wordpress',
                    'source_post_id' => $postId,
                    'source_post_url' => $post->guid,
                    'source_post_title' => $post->post_title,
                    'source_post_categories' => $post->categories ?? 'Uncategorized',
                    'chunk_index' => $index
                ]);

                $inserted = DB::connection('pgvector')->insert("
                    INSERT INTO vectors (text, metadata, embedding, created_at, updated_at)
                    VALUES (:text, :metadata, :embedding::vector, NOW(), NOW())
                ", [
                    'text' => $chunkText,
                    'metadata' => $metadata,
                    'embedding' => $vectorString
                ]);

                if (!$inserted) {
                    throw new Exception("Database abstraction layer failed to write raw vector chunk insert statement.");
                }

                Log::info('Chunk block successfully embedded and stored in pgvector database.', [
                    'post_id'         => $postId,
                    'chunk_index'     => $index,
                    'huggingface_ms'  => $embeddingResult['duration']
                ]);
            }

            Log::info('Finished post extraction ingestion pipeline processing.', [
                'post_id'      => $postId,
                'total_chunks' => count($chunks)
            ]);

        } catch (Throwable $th) {
            Log::error('Pipeline exception intercepted during processing pipeline block extraction.', [
                'post_id' => $postId,
                'message' => $th->getMessage()
            ]);

            throw new Exception("Processing execution aborted for post ID {$postId}: " . $th->getMessage(), 0, $th);
        }
    }
}