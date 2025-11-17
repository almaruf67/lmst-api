<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkAttendanceRecorded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  EloquentCollection<int, Student>  $students
     * @param  array<string, int>  $statusSummary
     */
    public function __construct(
        public readonly User $actor,
        public readonly CarbonImmutable $attendanceDate,
        public readonly EloquentCollection $students,
        public readonly int $recordCount,
        public readonly array $statusSummary,
        public readonly ?string $className = null,
        public readonly ?string $section = null,
    ) {}
}
