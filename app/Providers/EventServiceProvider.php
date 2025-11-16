<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\BulkAttendanceRecorded;
use App\Listeners\SendAttendanceRecordedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        BulkAttendanceRecorded::class => [
            SendAttendanceRecordedNotification::class,
        ],
    ];
}
