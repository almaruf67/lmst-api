<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sanctum Token Service
 *
 * @context Handles Sanctum personal access token generation and refresh
 *
 * @pattern Unified response format {success, data, message, code}
 */
class SanctumTokenService
{
    /**
     * Issue access and refresh tokens for a user.
     *
     * @param  User  $user  Authenticated user
     * @return array{success: bool, data: array|null, message: string, code: int}
     */
    public function issueToken(User $user): array
    {
        try {
            $accessExpiry = $this->minutesToExpiry((int) config('sanctum.access_token_expiry', 60));
            $refreshExpiry = $this->minutesToExpiry((int) config('sanctum.refresh_token_expiry', 43200));

            $access = $user->createToken('access_token', ['*'], $accessExpiry);
            $refresh = $user->createToken('refresh_token', ['refresh'], $refreshExpiry);

            return [
                'success' => true,
                'data' => [
                    'access_token' => $access->plainTextToken,
                    'refresh_token' => $refresh->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_in' => (int) config('sanctum.access_token_expiry', 60) * 60,
                    'user' => $user,
                ],
                'message' => 'Token issued successfully',
                'code' => 200,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to issue token: '.$e->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Refresh access token using a refresh token (rotation strategy).
     *
     * @param  string  $refreshToken  Plain-text refresh token value
     * @return array{success: bool, data: array|null, message: string, code: int}
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $tokenModel = PersonalAccessToken::findToken($refreshToken);

            if (! $tokenModel || ! $tokenModel->can('refresh')) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Invalid refresh token',
                    'code' => 401,
                ];
            }

            if ($tokenModel->expires_at && Date::now()->greaterThan($tokenModel->expires_at)) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Refresh token expired',
                    'code' => 401,
                ];
            }

            /** @var User $user */
            $user = $tokenModel->tokenable;

            // Rotate refresh token: delete old one
            $tokenModel->delete();

            // Issue new pair
            return $this->issueToken($user);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to refresh token: '.$e->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Revoke current user's tokens (access + refresh).
     */
    public function revokeTokens(User $user): bool
    {
        try {
            $user->currentAccessToken()?->delete();
            // Optionally, revoke all refresh tokens as well
            $user->tokens()->whereJsonContains('abilities', 'refresh')->delete();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function minutesToExpiry(int $minutes): CarbonInterface
    {
        return Date::now()->addMinutes($minutes);
    }
}
