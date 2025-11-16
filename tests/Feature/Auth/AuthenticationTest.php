<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('logs in with valid credentials', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Token issued successfully')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email'],
            ],
        ]);
});

it('returns error with invalid credentials', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', 'Invalid credentials');
});

it('refreshes token successfully', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();
    $tokens = loginAndGetTokens($this, $user);

    $refreshResponse = $this->postJson('/api/refresh', [
        'refresh_token' => $tokens['refresh_token'],
    ]);

    $refreshResponse->assertOk()
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'user' => ['id'],
            ],
        ]);

    expect($refreshResponse->json('data.access_token'))->not->toBe($tokens['access_token']);
});

it('revokes token on logout', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();
    $tokens = loginAndGetTokens($this, $user);

    $logoutResponse = $this->withToken($tokens['access_token'])
        ->postJson('/api/logout');

    $logoutResponse->assertOk()
        ->assertJsonPath('message', 'Logged out');

    $this->withToken($tokens['access_token'])
        ->getJson('/api/me')
        ->assertUnauthorized();
});

it('returns authenticated user data', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();
    $tokens = loginAndGetTokens($this, $user);

    $meResponse = $this->withToken($tokens['access_token'])
        ->getJson('/api/me');

    $meResponse->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('message', 'Authenticated user');
});

/**
 * Helper to login a user through the API and return issued tokens.
 *
 * @return array{access_token: string, refresh_token: string}
 */
function loginAndGetTokens(TestCase $testCase, User $user): array
{
    $response = $testCase->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk();

    return [
        'access_token' => $response->json('data.access_token'),
        'refresh_token' => $response->json('data.refresh_token'),
    ];
}
