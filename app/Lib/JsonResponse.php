<?php

declare(strict_types=1);

namespace App\Lib;

use Illuminate\Http\JsonResponse as LaravelJsonResponse;

/**
 * Unified JSON Response Helper
 *
 * @context Formats JSON responses consistently across the API
 *
 * @pattern Lightweight wrapper returning { message, data, version } with proper HTTP codes
 */
class JsonResponse
{
    /**
     * HTTP status text mapping (subset used for fallbacks)
     *
     * @var array<int, string>
     */
    public static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        207 => 'Multi-Status',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
    ];

    /**
     * API version identifier
     */
    public string $version = 'v1';

    /**
     * Build a success JSON response
     */
    public static function success(mixed $data = [], ?string $msg = 'Success', mixed $to = null, int $code = 200): LaravelJsonResponse
    {
        $message = self::normalizeMessage($msg, $code);
        $payload = ['message' => $message, 'data' => $data, 'version' => 'v1'];
        if ($to !== null) {
            $payload['to'] = $to;
        }

        return new LaravelJsonResponse($payload, $code);
    }

    /**
     * Build a warning JSON response (partial success)
     */
    public static function warning(mixed $data = [], ?string $msg = 'Warning', mixed $to = null, int $code = 207): LaravelJsonResponse
    {
        $message = self::normalizeMessage($msg, $code);
        $payload = ['message' => $message, 'data' => $data, 'version' => 'v1'];
        if ($to !== null) {
            $payload['to'] = $to;
        }

        return new LaravelJsonResponse($payload, $code);
    }

    /**
     * Build an error JSON response
     *
     * @param  array<int,int>  $allowCode  Limit codes when needed (default allows common ones)
     */
    public static function error(string $message = 'error', int $code = 400, array $allowCode = [404, 500, 401, 429], mixed $data = []): LaravelJsonResponse
    {
        $allow = array_merge([400, 401, 403, 404, 422, 429, 500], $allowCode);
        if (! in_array($code, $allow, true)) {
            $code = 400;
        }
        $payload = ['message' => self::normalizeMessage($message, $code), 'data' => $data, 'version' => 'v1'];

        return new LaravelJsonResponse($payload, $code);
    }

    private static function normalizeMessage(?string $message, int $code): string
    {
        if ($message !== null && $message !== '') {
            return str_replace('_', ' ', $message);
        }

        return self::$statusTexts[$code] ?? 'OK';
    }
}
