<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Auth Service
 *
 * @context Handles login/logout/me flows and delegates token issuance
 *
 * @pattern Service layer with unified response format and proper typing
 */
class AuthService
{
    public function __construct(
        private readonly SanctumTokenService $tokenService
    ) {}

    /**
     * Attempt login using email/password and issue tokens.
     *
     * @return array{success: bool, data: array|null, message: string, code: int}
     */
    public function login(string $email, string $password): array
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Invalid credentials',
                'code' => 401,
            ];
        }

        /** @var User $user */
        $user = Auth::user();

        return $this->tokenService->issueToken($user);
    }

    /**
     * Logout current user and revoke tokens.
     *
     * @return array{success: bool, data: array|null, message: string, code: int}
     */
    public function logout(User $user): array
    {
        $ok = $this->tokenService->revokeTokens($user);

        return [
            'success' => $ok,
            'data' => null,
            'message' => $ok ? 'Logged out' : 'Failed to logout',
            'code' => $ok ? 200 : 500,
        ];
    }

    /**
     * Return current authenticated user data.
     *
     * @return array{success: bool, data: array|null, message: string, code: int}
     */
    public function me(User $user): array
    {
        return [
            'success' => true,
            'data' => $user,
            'message' => 'Authenticated user',
            'code' => 200,
        ];
    }
}
