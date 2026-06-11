<?php

namespace App\Contracts;

interface Embedding
{
    /**
     * @return array{vector: array, duration: float}
     */
    public function generate(string $text, string $type = 'query'): array;
}