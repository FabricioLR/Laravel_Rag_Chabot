<?php

use App\Services\Embedding;
use App\Services\Knowledge;
use App\Services\LLM;
use Mockery;

/**
 * Test case for successful RAG Chatbot execution pipeline with sessionId.
 */
it('successfully generates an answer from user input with session tracking', function () {
    $userInput = 'Como configuro o faturamento no ERP?';
    $sessionId = 'test_session_12345';
    
    $this->mock(Embedding::class, function ($mock) use ($userInput) {
        $mock->shouldReceive('generate')
            ->once()
            ->with($userInput)
            ->andReturn([
                'vector' => [0.1, 0.2, 0.3],
                'duration' => 120.5
            ]);
    });

    $this->mock(Knowledge::class, function ($mock) use ($userInput, $sessionId) {
        $mock->shouldReceive('searchContext')
            ->once()
            ->with($userInput, [0.1, 0.2, 0.3], $sessionId)
            ->andReturn([
                'context' => "<context_1>\n[Fonte]: https://erp.docs/faturamento\n[Título]: Configuração\n[Texto]: Siga os passos X, Y, Z.\n</context_1>\n\n",
                'duration' => 45.2
            ]);
    });

    $this->mock(LLM::class, function ($mock) use ($userInput, $sessionId) {
        $mock->shouldReceive('generateAnswer')
            ->once()
            ->with($userInput, Mockery::type('string'), $sessionId)
            ->andReturn([
                'answer' => 'Para configurar o faturamento, siga os passos X, Y, Z disponíveis na documentação.',
                'duration' => 850.0
            ]);
    });

    $response = $this->postJson('/api/chat', [
        'chatInput' => $userInput,
        'sessionId' => $sessionId,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'sessionId' => $sessionId,
            'question'  => $userInput,
            'answer'    => 'Para configurar o faturamento, siga os passos X, Y, Z disponíveis na documentação.'
        ]);
});

/**
 * Test case for validation error handling.
 */
it('returns a validation error when fields are missing or malformed', function () {
    $response = $this->postJson('/api/chat', []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['chatInput', 'sessionId']);
});

/**
 * Test case for internal service failures handling gracefully with session context.
 */
it('returns a 500 status code when an internal pipeline step fails', function () {
    $userInput = 'Texto de teste';
    $sessionId = 'error_session_abc';

    $this->mock(Embedding::class, function ($mock) use ($userInput) {
        $mock->shouldReceive('generate')
            ->once()
            ->with($userInput)
            ->andThrow(new \Exception('Failed to generate text embeddings.'));
    });

    $response = $this->postJson('/api/chat', [
        'chatInput' => $userInput,
        'sessionId' => $sessionId,
    ]);

    $response->assertStatus(500);
    expect($response->json('error'))->toBe('Failed to generate text embeddings.');
});