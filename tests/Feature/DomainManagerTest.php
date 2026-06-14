<?php

use App\Services\DomainManager;
use App\Models\AllowedDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->domainManager = new DomainManager();
});


test('deve registrar um novo domínio ativo e remover a barra final da URL', function () {
    $name = 'Cliente Teste';
    $url = 'https://meuerp.com.br/';

    $allowedDomain = $this->domainManager->register($name, $url);

    expect($allowedDomain)->toBeInstanceOf(AllowedDomain::class)
        ->and($allowedDomain->name)->toBe($name)
        ->and($allowedDomain->domain)->toBe('https://meuerp.com.br')
        ->and($allowedDomain->is_active)->toBeTrue();

    $this->assertDatabaseHas('allowed_domains', [
        'name' => $name,
        'domain' => 'https://meuerp.com.br',
    ]);
});

test('deve revogar e deletar o perfil de acesso de um domínio existente', function () {
    $domain = AllowedDomain::factory()->create();

    $result = $this->domainManager->revoke($domain->id);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('allowed_domains', ['id' => $domain->id]);
});

test('deve lançar exceção ao tentar revogar um ID de domínio inexistente', function () {
    expect(fn () => $this->domainManager->revoke(9999))
        ->toThrow(Exception::class, 'Could not complete domain revocation processing.');
});

test('deve validar com sucesso correspondências exatas de domínios', function () {
    $domainRecord = AllowedDomain::factory()->create([
        'domain' => 'https://app.transoft.com.br',
        'is_active' => true,
    ]);

    $token = $domainRecord->token;

    expect($this->domainManager->verify($token, 'https://app.transoft.com.br'))->toBeTrue()
        ->and($this->domainManager->verify($token, 'https://app.transoft.com.br/'))->toBeTrue();
});

test('deve rejeitar validação se o token for inválido ou estiver inativo', function () {
    $domainRecord = AllowedDomain::factory()->create([
        'domain' => 'https://app.transoft.com.br',
        'is_active' => false,
    ]);

    $token = $domainRecord->token;

    expect($this->domainManager->verify($token, 'https://app.transoft.com.br'))->toBeFalse()
        ->and($this->domainManager->verify('token-inexistente', 'https://app.transoft.com.br'))->toBeFalse();
});

test('deve validar corretamente subdomínios usando máscara de curinga (wildcard)', function ($registeredPattern, $incomingOrigin, $shouldPass) {
    $domainRecord = AllowedDomain::factory()->create([
        'domain' => $registeredPattern,
        'is_active' => true,
    ]);

    $token = $domainRecord->token;

    expect($this->domainManager->verify($token, $incomingOrigin))->toBe($shouldPass);
})->with([
    ['https://*.teste.com.br',               'https://sub.teste.com.br',           true],
    ['https://*.teste.com.br',               'https://homolog.sub.teste.com.br',   true],
    ['https://*teste.com.br',                'https://www.teste.com.br',           true],
    ['https://*.teste.com.br',               'https://teste.com.br',               false], 
    ['https://*.teste.com.br',               'https://outrodomínio.com.br',        false],
    ['*',                                    'https://qualquer-origem.com',        true],  
    ['https://erp.transoft.com.br*',         'https://erp.transoft.com.br/api/v1', true],  
    ['https://erp.transoft.com.br*',         'https://erp.transoft.com.br',        true],  
]);