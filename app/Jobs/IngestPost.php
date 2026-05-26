<?php

namespace App\Jobs;

use App\Services\TextSplitter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Support\Facades\Cache;
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
        $lastIndexedPosts = Cache::get('wp_last_indexed_posts', [0]);
        $result = array_diff($lastIndexedPosts, [$this->postData['id']]);
        Cache::put('wp_last_indexed_posts', $result);
        Log::error('Job failed: ' . $exception->getMessage());
    }

    public function handle(): void
    {
        $postId = $this->postData['id'];
        $overrideIndexing = $this->postData['override_indexing'] ?? false;

        try {
            if ($overrideIndexing) {
                Log::info("Override de indexação ativado para post ID {$postId}. Forçando reindexação.");
                DB::connection('pgvector')->statement("DELETE FROM vectors WHERE metadata->>'source_post_id' = :postId", [
                    'postId' => (string)$postId
                ]);
            }

            Log::info("Starting ingestion processing for post ID: {$postId}");

            $wp_table_prefix = env('WP_DB_TABLE_PREFIX', 'wp_');

            $posts = DB::connection('wordpress')->select("
                    SELECT 
                        p.post_title, p.guid, p.post_content,
                        (
                            SELECT GROUP_CONCAT(t.name SEPARATOR ', ')
                            FROM {$wp_table_prefix}term_relationships r
                            INNER JOIN {$wp_table_prefix}term_taxonomy tt ON r.term_taxonomy_id = tt.term_taxonomy_id
                            INNER JOIN {$wp_table_prefix}terms t ON tt.term_id = t.term_id
                            WHERE r.object_id = p.ID 
                            AND tt.taxonomy = 'category'
                        ) AS categories
                    FROM {$wp_table_prefix}posts p
                    WHERE 
                        p.ID = :postId
                    LIMIT 1;
                ", [
                    ':postId' => $postId
                ]);

            if (empty($posts)) {
                throw new Exception("Post ID {$postId} não foi encontrado no WordPress.");
            }
            
            $post = $posts[0];

            if (!$post || empty($post->post_content)) {
                throw new Exception("Conteúdo do post ID {$postId} não foi encontrado ou está vazio.");
            }

            $rawHtmlContent = $post->post_content;

            //$cleanHtmlContent = str_replace("\n", '', $rawHtmlContent);
            $cleanHtmlContent = preg_replace('//s', '', $rawHtmlContent);
            $cleanHtmlContent = trim($cleanHtmlContent);
        
            $converter = new HtmlConverter([
                'strip_tags' => true,        
                'hard_break' => true,        
                'italic_style' => '_',       
                'bold_style' => '**',       
            ]);

            $markdownContent = $converter->convert($cleanHtmlContent);

            $chunks = TextSplitter::split($markdownContent, maxWords: 500, overlapWords: 50);

            foreach ($chunks as $index => $chunkText) {
                $hfStartTime = microtime(true);
                $hfResponse = Http::withToken(env('HUGGINGFACE_API_KEY'))
                    ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-large/pipeline/feature-extraction', [
                        'inputs' => "passage: " . $chunkText,
                    ]);
                $hfDuration = round((microtime(true) - $hfStartTime) * 1000, 2);

                if ($hfResponse->failed()) {
                    throw new Exception("HF Embedding generation failed for post {$postId}, chunk {$index} " . json_encode([
                        'status' => $hfResponse->status(),
                        'body' => $hfResponse->body()
                    ]));
                }

                $vectorArray = $hfResponse->json();
                $vectorString = '[' . implode(',', $vectorArray) . ']';

                $metadata = json_encode([
                    'source' => 'wordpress',
                    'source_post_id' => $postId,
                    'source_post_url' => $post->guid,
                    'source_post_title' => $post->post_title,
                    'source_post_categories' => $post->categories ?? 'Uncategorized',
                    'chunk_index' => $index
                ]);

                $result = DB::connection('pgvector')->insert("
                    INSERT INTO vectors (text, metadata, embedding, created_at, updated_at)
                    VALUES (:text, :metadata, :embedding::vector, NOW(), NOW())
                ", [
                    'text' => $chunkText,
                    'metadata' => $metadata,
                    'embedding' => $vectorString
                ]);

                if (!$result){
                    throw new Exception("Insert into vectors table failed");
                }

                Log::info("Chunk {$index} for post {$postId} successfully indexed.", ['hf_time_ms' => $hfDuration]);
            }

            Log::info("Finished ingestion pipeline for post ID: {$postId}. Total chunks indexed: " . count($chunks));
        } catch (Throwable $th) {
            throw new Exception("Error occurred while processing post ID: {$postId} " . $th->getMessage());
        }
    }

}
