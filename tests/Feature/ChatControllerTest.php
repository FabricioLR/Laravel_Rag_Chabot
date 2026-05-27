<?php

use App\Services\Embedding;
use App\Services\Knowledge;
use App\Services\LLM;
use Mockery;

/**
 * Test case for successful RAG Chatbot execution pipeline.
 */
it('successfully generates an answer from user input', function () {
    $userInput = 'Como configuro o faturamento no ERP?';
    
    // 1. Mock the Embedding Service
    $mockedEmbedding = $this->mock(Embedding::class, function ($mock) use ($userInput) {
        $mock->shouldReceive('generate')
            ->once()
            ->with($userInput)
            ->andReturn([
                'vector' => [0.1, 0.2, 0.3], // Mocked array sequence
                'duration' => 120.5
            ]);
    });

    // 2. Mock the KnowledgeBase Service (Bypasses executing raw pgvector SQL in testing)
    $mockedKnowledgeBase = $this->mock(Knowledge::class, function ($mock) use ($userInput) {
        $mock->shouldReceive('searchContext')
            ->once()
            ->with($userInput, [0.1, 0.2, 0.3])
            ->andReturn([
                'context' => "<context_1>\n[Fonte]: https://erp.docs/faturamento\n[Título]: Configuração\n[Texto]: Siga os passos X, Y, Z.\n</context_1>\n\n",
                'duration' => 45.2
            ]);
    });

    // 3. Mock the LLM Service
    $mockedLlm = $this->mock(LLM::class, function ($mock) use ($userInput) {
        $mock->shouldReceive('generateAnswer')
            ->once()
            ->with($userInput, Mockery::type('string'))
            ->andReturn([
                'answer' => 'Para configurar o faturamento, siga os passos X, Y, Z disponíveis na documentação.',
                'duration' => 850.0
            ]);
    });

    // Execute the POST request to the API route
    $response = $this->postJson('/api/chat', [
        'chatInput' => $userInput,
    ]);

    // Assertions
    $response->assertStatus(200)
        ->assertJson([
            'question' => $userInput,
            'answer' => 'Para configurar o faturamento, siga os passos X, Y, Z disponíveis na documentação.'
        ]);
});

/**
 * Test case for validation error handling.
 */
it('returns a validation error when chatInput is missing', function () {
    $response = $this->postJson('/api/chat', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['chatInput']);
});

/**
 * Test case for internal service failures handling gracefully.
 */
it('returns a 500 status code when an internal pipeline step fails', function () {
    $userInput = 'Texto de teste';

    // Force the EmbeddingService to throw an exception simulating an API breakdown
    $this->mock(Embedding::class, function ($mock) use ($userInput) {
        $mock->shouldReceive('generate')
            ->once()
            ->with($userInput)
            ->andThrow(new \Exception('Failed to generate text embeddings.'));
    });


    $response = $this->postJson('/api/chat', [
        'chatInput' => $userInput,
    ]);

    $response->assertStatus(500);

    expect($response->json('error'))->toBe('Failed to generate text embeddings.');
});