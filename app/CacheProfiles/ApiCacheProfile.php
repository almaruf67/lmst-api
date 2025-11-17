<?php

declare(strict_types=1);

namespace App\CacheProfiles;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom API cache profile tailored for the LMST project.
 *
 * @context Enables smart API caching only for safe, idempotent requests
 *
 * @pattern Implements Spatie's CacheProfile with role-aware filtering
 */
class ApiCacheProfile implements CacheProfile
{
    /**
     * Determine if caching is enabled for the current request.
     */
    public function enabled(Request $request): bool
    {
        return (bool) config('responsecache.enabled', true);
    }

    /**
     * Determine if the incoming request should be cached.
     */
    public function shouldCacheRequest(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax()) {
            return false;
        }

        if ($request->user()?->isAdmin()) {
            return false;
        }

        if ($request->is('telescope*', 'pulse*', 'docs*')) {
            return false;
        }

        if ($request->is('api/*/reports*', 'api/*/search*', 'api/*/filter*')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the outgoing response should be cached.
     */
    public function shouldCacheResponse(Response $response): bool
    {
        if (! $response->isSuccessful()) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_starts_with($contentType, 'application/json');
    }

    /**
     * Determine cache lifetime for the request.
     */
    public function cacheRequestUntil(Request $request): DateTimeInterface
    {
        if ($request->is('api/*/students*')) {
            return CarbonImmutable::now()->addMinutes(30);
        }

        if ($request->is('api/*/my-students*')) {
            return CarbonImmutable::now()->addMinutes(10);
        }

        return CarbonImmutable::now()->addMinutes((int) config('responsecache.default_ttl', 15));
    }

    /**
     * Define cache suffix so each teacher sees their own cached data.
     */
    public function useCacheNameSuffix(Request $request): string
    {
        if (! $request->user()) {
            return 'guest';
        }

        return sprintf('%s-%d', $request->user()->user_type->value, $request->user()->getKey());
    }
}
