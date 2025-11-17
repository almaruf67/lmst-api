<?php

declare(strict_types=1);

namespace App\Exports\Attendance;

use App\Exports\Attendance\Sheets\DailyTotalsSheet;
use App\Exports\Attendance\Sheets\RecordsSheet;
use App\Exports\Attendance\Sheets\SummarySheet;
use Illuminate\Contracts\Support\Arrayable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonthlyAttendanceExport implements WithMultipleSheets
{
    public function __construct(private readonly array $report) {}

    /**
     * @return array<int, Arrayable>
     */
    public function sheets(): array
    {
        return [
            new SummarySheet($this->report),
            new DailyTotalsSheet($this->report),
            new RecordsSheet($this->report),
        ];
    }
}
