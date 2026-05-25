<?php

namespace App\Services;

class TextSplitter
{
    /**
     * Splits a document by headings or paragraphs safely.
     */
    public static function split(string $text, int $maxWords, int $overlapWords): array
    {
        // 1. Clean up extreme line breaks (prevent single orphaned characters)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // 2. Explode text by words
        $words = preg_split('/\s+/', $text);
        $totalWords = count($words);
        $chunks = [];
        
        $i = 0;
        while ($i < $totalWords) {
            // Take a chunk of words
            $slice = array_slice($words, $i, $maxWords);
            $chunkText = implode(' ', $slice);

            // Filter out empty or uselessly small markdown fragments (e.g., lone triple backticks)
            $cleanTest = preg_replace('/[`\s\n\r]/', '', $chunkText);
            if (strlen($cleanTest) > 15) {
                $chunks[] = trim($chunkText);
            }

            // Move pointer forward while accounting for overlap
            $i += ($maxWords - $overlapWords);
        }

        return $chunks;
    }
}