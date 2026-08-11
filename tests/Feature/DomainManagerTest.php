<?php

use App\Services\DomainManager;
use App\Models\AllowedDomain;
use App\Models\ClientToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->domainManager = new DomainManager();
});

test('must create a new active client token', function () {
    $name = 'Cliente Teste';

    $clientToken = $this->domainManager->createToken($name);

    expect($clientToken)->toBeInstanceOf(ClientToken::class)
        ->and($clientToken->name)->toBe($name)
        ->and($clientToken->token)->not->toBeEmpty()
        ->and($clientToken->is_active)->toBeTrue();

    $this->assertDatabaseHas('client_tokens', [
        'name'      => $name,
        'is_active' => true,
    ]);
});

test('must register a new domain associeted with a client token', function () {
    $clientToken = ClientToken::factory()->create();
    $url = 'https://meuerp.com.br/';

    $allowedDomain = $this->domainManager->addDomain($clientToken->id, $url);

    expect($allowedDomain)->toBeInstanceOf(AllowedDomain::class)
        ->and($allowedDomain->client_token_id)->toBe($clientToken->id)
        ->and($allowedDomain->domain)->toBe('https://meuerp.com.br')
        ->and($allowedDomain->is_active)->toBeTrue();

    $this->assertDatabaseHas('allowed_domains', [
        'client_token_id' => $clientToken->id,
        'domain'          => 'https://meuerp.com.br',
    ]);
});

test('must revoke and delete a domain', function () {
    $domain = AllowedDomain::factory()->create();

    $result = $this->domainManager->revokeDomain($domain->id);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('allowed_domains', ['id' => $domain->id]);
});

test('must throw an exception when attempting to revoke a non-existent domain', function () {
    expect(fn () => $this->domainManager->revokeDomain(9999))
        ->toThrow(Exception::class, 'Could not complete domain revocation.');
});

test('must successfully validate exact matches when multiple domains are registered to the same token', function () {
    $clientToken = ClientToken::factory()->create();

    AllowedDomain::factory()->create([
        'client_token_id' => $clientToken->id,
        'domain'          => 'https://app.transoft.com.br',
        'is_active'       => true,
    ]);

    AllowedDomain::factory()->create([
        'client_token_id' => $clientToken->id,
        'domain'          => 'https://admin.transoft.com.br',
        'is_active'       => true,
    ]);

    $token = $clientToken->token;

    expect($this->domainManager->verify($token, 'https://app.transoft.com.br'))->toBeTrue()
        ->and($this->domainManager->verify($token, 'https://app.transoft.com.br/'))->toBeTrue()
        ->and($this->domainManager->verify($token, 'https://admin.transoft.com.br'))->toBeTrue();
});

test('must reject validation if the token is inactive or non-existent', function () {
    $inactiveToken = ClientToken::factory()->create(['is_active' => false]);
    
    AllowedDomain::factory()->create([
        'client_token_id' => $inactiveToken->id,
        'domain'          => 'https://app.transoft.com.br',
        'is_active'       => true,
    ]);

    expect($this->domainManager->verify($inactiveToken->token, 'https://app.transoft.com.br'))->toBeFalse()
        ->and($this->domainManager->verify('token-inexistente', 'https://app.transoft.com.br'))->toBeFalse();
});

test('must correctly validate subdomain using a wildcard mask', function ($registeredPattern, $incomingOrigin, $shouldPass) {
    $clientToken = ClientToken::factory()->create(['is_active' => true]);

    AllowedDomain::factory()->create([
        'client_token_id' => $clientToken->id,
        'domain'          => $registeredPattern,
        'is_active'       => true,
    ]);

    expect($this->domainManager->verify($clientToken->token, $incomingOrigin))->toBe($shouldPass);
})->with([
    ['https://*.teste.com.br',       'https://sub.teste.com.br',          true],
    ['https://*.teste.com.br',       'https://homolog.sub.teste.com.br',  true],
    ['https://*teste.com.br',        'https://www.teste.com.br',          true],
    ['https://*.teste.com.br',       'https://teste.com.br',              false],
    ['https://*.teste.com.br',       'https://outrodomínio.com.br',       false],
    ['*',                            'https://qualquer-origem.com',       true],
    ['https://erp.transoft.com.br*', 'https://erp.transoft.com.br/api/v1', true],
    ['https://erp.transoft.com.br*', 'https://erp.transoft.com.br',        true],
]);