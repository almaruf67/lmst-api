<?php

declare(strict_types=1);

use App\CacheProfiles\ApiCacheProfile;
use Spatie\ResponseCache\Replacers\CsrfTokenReplacer;

return [
    'enabled' => env('RESPONSE_CACHE_ENABLED', true),

    'cache_profile' => ApiCacheProfile::class,

    'cache_bypass_header' => [
        'name' => env('CACHE_BYPASS_HEADER_NAME', 'X-Skip-Cache'),
        'value' => env('CACHE_BYPASS_HEADER_VALUE', '1'),
    ],

    'cache_lifetime_in_seconds' => (int) env('RESPONSE_CACHE_LIFETIME', 60 * 30),

    'default_ttl' => (int) env('RESPONSE_CACHE_DEFAULT_TTL_MINUTES', 15),

    'add_cache_time_header' => env('APP_DEBUG', false),

    'cache_time_header_name' => env('RESPONSE_CACHE_HEADER_NAME', 'lmst-responsecache'),

    'add_cache_age_header' => env('RESPONSE_CACHE_AGE_HEADER', false),

    'cache_age_header_name' => env('RESPONSE_CACHE_AGE_HEADER_NAME', 'lmst-responsecache-age'),

    'cache_store' => env('RESPONSE_CACHE_STORE', env('CACHE_STORE', 'database')),

    'use_cache_tags' => true,

    'cache_directory' => storage_path('app/response-cache'),

    'replacers' => [
        CsrfTokenReplacer::class,
    ],

    'cache_tag' => env('RESPONSE_CACHE_TAG', 'api-response'),

    'hasher' => \Spatie\ResponseCache\Hasher\DefaultHasher::class,

    'serializer' => \Spatie\ResponseCache\Serializers\DefaultSerializer::class,

    'except' => [
        '/telescope*',
        '/pulse*',
        '/docs*',
        '/api/login',
        '/api/refresh',
        '/api/logout',
        '/api/*/reports*',
        '/api/*/export*',
        '/api/*/search*',
    ],
];
