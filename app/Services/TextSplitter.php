<?php

namespace App\Services;

class TextSplitter
{
    /**
     * Splits a document by headings or paragraphs safely.
     */
    public static function split(string $text, int $maxWords, int $overlapWords): array
    {
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        $words = preg_split('/\s+/', $text);
        $totalWords = count($words);
        $chunks = [];
        
        $i = 0;
        while ($i < $totalWords) {
            $slice = array_slice($words, $i, $maxWords);
            $chunkText = implode(' ', $slice);

            $cleanTest = preg_replace('/[`\s\n\r]/', '', $chunkText);
            if (strlen($cleanTest) > 15) {
                $chunks[] = trim($chunkText);
            }

            $i += ($maxWords - $overlapWords);
        }

        return $chunks;
    }
}