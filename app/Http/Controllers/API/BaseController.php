<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Lib\JsonResponse;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;

/**
 * Base API Controller
 *
 * @context All API controllers extend this for consistent response formatting
 *
 * @pattern Uses JsonResponse helper for standardized responses across endpoints
 */
class BaseController extends Controller
{
    /**
     * Send success response
     *
     * @param  mixed  $result  Data to return
     * @param  string  $message  Success message
     * @param  int  $code  HTTP status code (default 200)
     */
    protected function sendResponse(mixed $result, string $message = 'Success', int $code = 200): LaravelJsonResponse
    {
        return JsonResponse::success($result, $message, null, $code);
    }

    /**
     * Send error response
     *
     * @param  string  $error  Error message
     * @param  array<string, mixed>  $errorMessages  Validation errors or details
     * @param  int  $code  HTTP status code (default 400)
     */
    protected function sendError(string $error, array $errorMessages = [], int $code = 400): LaravelJsonResponse
    {
        return JsonResponse::error($error, $code, [$code], $errorMessages);
    }

    /**
     * Send warning response
     *
     * @param  string  $message  Warning message
     * @param  mixed  $result  Optional data
     * @param  int  $code  HTTP status code (default 207)
     */
    protected function sendWarning(string $message, mixed $result = null, int $code = 207): LaravelJsonResponse
    {
        return JsonResponse::warning($result, $message, null, $code);
    }
}
