<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserType;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use App\Support\Reports\MonthlyAttendanceReportFormatter;
use Illuminate\Console\Command;
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

        $csv = MonthlyAttendanceReportFormatter::toCsv($report);

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
     * Build a synthetic admin user for CLI-driven report generation.
     */
    private function buildSystemAdminUser(): User
    {
        $user = new User;
        $user->user_type = UserType::Admin;

        return $user;
    }
}
