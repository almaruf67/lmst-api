<?php

declare(strict_types=1);

namespace App\Exports\Attendance\Sheets;

use App\Support\Reports\MonthlyAttendanceReportFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class SummarySheet implements FromArray, WithTitle
{
    public function __construct(private readonly array $report) {}

    public function title(): string
    {
        return 'Summary';
    }

    public function array(): array
    {
        $rows = MonthlyAttendanceReportFormatter::metadata($this->report);
        $rows[] = [];
        $rows[] = ['Total Records', $this->report['summary']['total_records'] ?? 0];
        $rows[] = [];

        foreach (MonthlyAttendanceReportFormatter::summaryTable($this->report) as $tableRow) {
            $rows[] = $tableRow;
        }

        return $rows;
    }
}
