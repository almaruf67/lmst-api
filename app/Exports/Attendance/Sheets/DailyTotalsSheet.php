<?php

declare(strict_types=1);

namespace App\Exports\Attendance\Sheets;

use App\Support\Reports\MonthlyAttendanceReportFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailyTotalsSheet implements FromArray, WithTitle
{
    public function __construct(private readonly array $report) {}

    public function title(): string
    {
        return 'Daily Totals';
    }

    public function array(): array
    {
        return MonthlyAttendanceReportFormatter::dailyTotalsTable($this->report);
    }
}
