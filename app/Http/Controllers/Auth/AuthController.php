<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Services\Auth\AuthService;
use App\Services\Auth\SanctumTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Authentication Controller
 *
 * @context Handles login, refresh, logout, and me endpoints
 *
 * @pattern Thin controller delegating to service layer, using BaseController responses
 */
class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly SanctumTokenService $tokenService
    ) {}

    /**
     * Login and issue tokens.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->validated('email'),
                $request->validated('password')
            );

            return $result['success']
                ? $this->sendResponse($result['data'], $result['message'])
                : $this->sendError($result['message'], $result['data'] ?? [], $result['code']);
        } catch (\Throwable $e) {
            Log::error('Login failed', ['error' => $e->getMessage()]);

            return $this->sendError('Login failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Refresh tokens using refresh token (rotation).
     */
    public function refresh(RefreshRequest $request): JsonResponse
    {
        try {
            $result = $this->tokenService->refreshToken($request->validated('refresh_token'));

            return $result['success']
                ? $this->sendResponse($result['data'], $result['message'])
                : $this->sendError($result['message'], $result['data'] ?? [], $result['code']);
        } catch (\Throwable $e) {
            Log::error('Token refresh failed', ['error' => $e->getMessage()]);

            return $this->sendError('Token refresh failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Revoke current tokens (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return $this->sendError('Not authenticated', [], 401);
            }

            $result = $this->authService->logout($user);

            return $result['success']
                ? $this->sendResponse(null, $result['message'])
                : $this->sendError($result['message'], [], $result['code']);
        } catch (\Throwable $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);

            return $this->sendError('Logout failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get authenticated user info.
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return $this->sendError('Not authenticated', [], 401);
            }

            $result = $this->authService->me($user);

            return $this->sendResponse($result['data'], $result['message']);
        } catch (\Throwable $e) {
            Log::error('Fetching user failed', ['error' => $e->getMessage()]);

            return $this->sendError('Failed to fetch user', ['error' => $e->getMessage()], 500);
        }
    }
}
