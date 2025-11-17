<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

/**
 * Register shared health checks for the application.
 *
 * @context Centralizes health monitoring configuration for Spatie Health
 *
 * @pattern Service provider that registers reusable check definitions
 */
class HealthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application's health checks.
     */
    public function boot(): void
    {
        Health::checks([
            DatabaseCheck::new()->name('database'),
            CacheCheck::new()->name('cache'),
            QueueCheck::new()->name('queue'),
            RedisCheck::new()->name('redis'),
            UsedDiskSpaceCheck::new()
                ->name('disk')
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(85),
        ]);
    }
}
