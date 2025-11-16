<?php

use App\Lib\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        channels: __DIR__ . '/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'cache.response' => CacheResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(static function (ValidationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return JsonResponse::error(
                __('validation.failed'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [Response::HTTP_UNPROCESSABLE_ENTITY],
                $exception->errors()
            );
        });

        $exceptions->renderable(static function (AuthenticationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return JsonResponse::error(
                $exception->getMessage() ?: __('auth.unauthenticated'),
                Response::HTTP_UNAUTHORIZED,
                [Response::HTTP_UNAUTHORIZED]
            );
        });

        $exceptions->renderable(static function (AuthorizationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return JsonResponse::error(
                $exception->getMessage() ?: __('auth.not_authorized'),
                Response::HTTP_FORBIDDEN,
                [Response::HTTP_FORBIDDEN]
            );
        });

        $exceptions->renderable(static function (ModelNotFoundException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $model = class_basename($exception->getModel() ?? 'resource');

            $message = sprintf('%s not found.', $model ?: 'Resource');

            return JsonResponse::error(
                $message,
                Response::HTTP_NOT_FOUND,
                [Response::HTTP_NOT_FOUND]
            );
        });

        $exceptions->renderable(static function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return JsonResponse::error(
                $exception->getMessage() ?: (Response::$statusTexts[$exception->getStatusCode()] ?? 'Error'),
                $exception->getStatusCode(),
                [$exception->getStatusCode()]
            )->withHeaders($exception->getHeaders());
        });
    })->create();
