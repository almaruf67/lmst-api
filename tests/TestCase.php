<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Application;
use Laravel\Telescope\Telescope;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the application.
     */
    public function createApplication(): Application
    {
        $overrides = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'MAIL_MAILER' => 'array',
            'TELESCOPE_ENABLED' => 'false',
            'PULSE_ENABLED' => 'false',
        ];

        foreach ($overrides as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $app = require __DIR__ . '/../bootstrap/app.php';

        if (file_exists(__DIR__ . '/../.env.testing')) {
            $app->loadEnvironmentFrom('.env.testing');
        }

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('telescope.enabled', false);
        $app['config']->set('telescope.storage.database.connection', 'sqlite');
        $app['db']->purge('mysql');
        $app['db']->setDefaultConnection('sqlite');

        return $app;
    }
}
