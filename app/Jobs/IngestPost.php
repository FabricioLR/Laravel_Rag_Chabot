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

class IngestPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $postData;

    public function __construct(array $postData)
    {
        $this->postData = $postData;
    }

    public function handle(): void
    {
        $postId = $this->postData['id'];
        $title = $this->postData['title'];
        $categories = $this->postData['categories'];
        //$rawHtmlContent = $this->postData['content'];
        $url = $this->postData['url'];

        Log::info("Starting ingestion processing for post ID: {$postId}");

        $post = DB::connection('wordpress')
            ->table(env('WP_DB_TABLE_PREFIX', 'wp_') . 'posts')
            ->where('ID', $postId)
            ->select('post_content')
            ->first();

        if (!$post || empty($post->post_content)) {
            Log::warning("Conteúdo do post ID {$postId} não foi encontrado ou está vazio.");
            return;
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
                Log::error("HF Embedding generation failed for post {$postId}, chunk {$index}", [
                    'status' => $hfResponse->status(),
                    'body' => $hfResponse->body()
                ]);
                continue;
            }

            $vectorArray = $hfResponse->json();
            $vectorString = '[' . implode(',', $vectorArray) . ']';

            // 3. Build Metadata Payload
            $metadata = json_encode([
                'source' => 'wordpress',
                'source_post_id' => $postId,
                'source_post_url' => $url,
                'source_post_title' => $title,
                'source_post_categories' => $categories,
                'chunk_index' => $index
            ]);

            DB::connection('pgvector')->statement("
                INSERT INTO vectors (text, metadata, embedding, created_at, updated_at)
                VALUES (:text, :metadata, :embedding::vector, NOW(), NOW())
            ", [
                'text' => $chunkText,
                'metadata' => $metadata,
                'embedding' => $vectorString
            ]);

            Log::info("Chunk {$index} for post {$postId} successfully indexed.", ['hf_time_ms' => $hfDuration]);
        }

        Log::info("Finished ingestion pipeline for post ID: {$postId}. Total chunks indexed: " . count($chunks));
    }
}
