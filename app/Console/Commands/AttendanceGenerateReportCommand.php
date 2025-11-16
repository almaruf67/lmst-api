<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Generate CSV attendance reports for a class over a given month.
 *
 * @context Automates exporting attendance summaries for ops or reporting teams outside the API layer
 *
 * @pattern Console command delegating to service layer for data retrieval, then persisting CSV output to storage
 */
class AttendanceGenerateReportCommand extends Command
{
    /**
     * @param  AttendanceService  $attendanceService  Injected attendance orchestrator
     */
    public function __construct(private readonly AttendanceService $attendanceService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:generate-report
        {month : Target month in Y-m format}
        {class : Class name to filter results by}
        {--section= : Optional section to further filter the class}
        {--path= : Optional custom relative storage path for the CSV output}';

    /**
     * The console command description.
     */
    protected $description = 'Generate a monthly attendance CSV report for a class.';

    /**
     * Execute the console command.
     *
     * @context CLI entry-point for exporting monthly attendance performance snapshots
     *
     * @pattern Delegates filtering to AttendanceService before persisting a CSV to local storage
     */
    public function handle(): int
    {
        $month = (string) $this->argument('month');
        $className = (string) $this->argument('class');
        $section = $this->option('section');
        $customPath = $this->option('path');

        try {
            $report = $this->attendanceService->generateMonthlyReport(
                $this->buildSystemAdminUser(),
                [
                    'month' => $month,
                    'class_name' => $className,
                    'section' => $section,
                ]
            );
        } catch (\Throwable $exception) {
            $this->error('Unable to generate attendance report: '.$exception->getMessage());
            report($exception);

            return SymfonyCommand::FAILURE;
        }

        $csv = $this->buildCsv($report);

        $path = $customPath ? ltrim((string) $customPath, '/\\') : sprintf(
            'reports/attendance-%s-%s.csv',
            Str::slug($className),
            $month
        );

        Storage::disk('local')->put($path, $csv);

        $this->info(sprintf('Attendance report saved to storage/app/%s', $path));

        return SymfonyCommand::SUCCESS;
    }

    /**
     * Transform the report payload into a CSV string.
     *
     * @param  array{filters: array{month:string,class_name:?string,section:?string}, summary: array{total_records:int,totals_by_status: array<string,int>}, daily_totals: array<string,int>, records: EloquentCollection<int, Attendance>}  $report
     * @return string CSV-formatted content ready for storage
     */
    private function buildCsv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, ['Month', $report['filters']['month']]);
        fputcsv($handle, ['Class', $report['filters']['class_name'] ?? 'All']);

        if ($report['filters']['section']) {
            fputcsv($handle, ['Section', $report['filters']['section']]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Date', 'Student Name', 'Student ID', 'Status', 'Note']);

        foreach ($report['records'] as $attendance) {
            $status = $this->resolveStatusValue($attendance->status);

            fputcsv($handle, [
                $attendance->attendance_date?->toDateString(),
                $attendance->student?->name,
                $attendance->student?->student_id,
                $status,
                $attendance->note ?? '',
            ]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Totals By Status']);

        foreach ($report['summary']['totals_by_status'] as $status => $total) {
            fputcsv($handle, [$status, $total]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Daily Totals']);

        foreach ($report['daily_totals'] as $date => $total) {
            fputcsv($handle, [$date, $total]);
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $contents;
    }

    /**
     * Build a synthetic admin user for CLI-driven report generation.
     */
    private function buildSystemAdminUser(): User
    {
        $user = new User;
        $user->user_type = UserType::Admin;

        return $user;
    }

    /**
     * Normalize enum-backed statuses to plain string values for CSV output.
     */
    private function resolveStatusValue(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return $status->value;
        }

        if ($status instanceof AttendanceStatus) {
            return $status->value;
        }

        return (string) $status;
    }
}
