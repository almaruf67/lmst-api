<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Loggable Trait
 *
 * @context Automatic audit trail tracking for models
 *
 * @pattern Hooks into Eloquent events (creating, updating) to set user and IP
 */
trait Loggable
{
    /**
     * Boot the trait and set up model events.
     */
    protected static function bootLoggable(): void
    {
        static::creating(function ($model): void {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
            $model->created_ip = Request::ip();
        });

        static::updating(function ($model): void {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
            $model->updated_ip = Request::ip();
        });
    }
}
