<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

/**
 * Customizable activity log entry model.
 *
 * @context Provides typed relations for audit log consumers
 *
 * @pattern Extends Spatie's base activity model to match local namespace
 */
class ActivityLog extends Activity
{
    // Intentionally left empty to allow future customization hooks
}
