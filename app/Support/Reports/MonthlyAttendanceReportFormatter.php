<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;

class MonthlyAttendanceReportFormatter
{
    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}, summary: array{total_records:int,totals_by_status: array<string,int>}, daily_totals: array<string,int>, records: EloquentCollection<int, Attendance>}  $report
     */
    public static function toCsv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        foreach (self::metaRows($report) as $row) {
            fputcsv($handle, $row);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Summary']);
        foreach (self::summaryTable($report) as $row) {
            fputcsv($handle, $row);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Daily Totals']);
        foreach (self::dailyTotalsTable($report) as $row) {
            fputcsv($handle, $row);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Detailed Records']);
        foreach (self::recordsTable($report) as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $contents;
    }

    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}, summary: array{total_records:int,totals_by_status: array<string,int>}, daily_totals: array<string,int>, records: EloquentCollection<int, Attendance>}  $report
     */
    public static function toArray(array $report): array
    {
        return [
            'metadata' => self::metadata($report),
            'summary' => [
                'headers' => ['Status', 'Total'],
                'rows' => array_slice(self::summaryTable($report), 1),
            ],
            'daily_totals' => [
                'headers' => ['Date', 'Total'],
                'rows' => array_slice(self::dailyTotalsTable($report), 1),
            ],
            'records' => [
                'headers' => self::recordHeaders(),
                'rows' => array_slice(self::recordsTable($report), 1),
            ],
        ];
    }

    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}}  $report
     */
    public static function metadata(array $report): array
    {
        return [
            ['Month', $report['filters']['month']],
            ['Class', $report['filters']['class_name'] ?? 'All'],
            ['Section', $report['filters']['section'] ?? 'All'],
        ];
    }

    /**
     * @param  array{summary: array{totals_by_status: array<string,int>}}  $report
     */
    public static function summaryTable(array $report): array
    {
        $rows = [['Status', 'Total']];

        foreach ($report['summary']['totals_by_status'] as $status => $total) {
            $rows[] = [self::formatStatus($status), $total];
        }

        return $rows;
    }

    /**
     * @param  array{daily_totals: array<string,int>}  $report
     */
    public static function dailyTotalsTable(array $report): array
    {
        $rows = [['Date', 'Total']];

        foreach ($report['daily_totals'] as $date => $total) {
            $rows[] = [$date, $total];
        }

        return $rows;
    }

    /**
     * @param  array{records: EloquentCollection<int, Attendance>}  $report
     */
    public static function recordsTable(array $report): array
    {
        $rows = [self::recordHeaders()];

        foreach ($report['records'] as $attendance) {
            $rows[] = [
                $attendance->attendance_date?->toDateString(),
                $attendance->student?->name,
                $attendance->student?->student_id,
                $attendance->student?->class_name,
                $attendance->student?->section,
                self::formatStatus($attendance->status),
                $attendance->note ?? '',
            ];
        }

        return $rows;
    }

    private static function recordHeaders(): array
    {
        return ['Date', 'Student Name', 'Student ID', 'Class', 'Section', 'Status', 'Note'];
    }

    private static function formatStatus(mixed $status): string
    {
        if ($status instanceof AttendanceStatus) {
            return Str::headline($status->value);
        }

        if (is_string($status)) {
            return Str::headline($status);
        }

        return Str::headline((string) $status);
    }

    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}}  $report
     */
    private static function metaRows(array $report): array
    {
        return array_merge(self::metadata($report), [[]]);
    }
}
