<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Exports\Attendance\MonthlyAttendanceExport;
use App\Http\Requests\Attendance\AttendanceReportRequest;
use App\Http\Requests\Attendance\BulkAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\Attendance\AttendanceService;
use App\Support\Reports\MonthlyAttendanceReportFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends BaseController
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    /**
     * Handle bulk attendance recording requests.
     */
    public function recordBulk(BulkAttendanceRequest $request): JsonResponse
    {
        $this->authorize('record', Attendance::class);

        $attendances = $this->attendanceService->recordBulk($request->user(), $request->validated());

        return $this->sendResponse([
            'attendances' => AttendanceResource::collection($attendances),
        ], 'Attendance recorded successfully');
    }

    /**
     * Provide a monthly attendance report.
     */
    public function monthlyReport(AttendanceReportRequest $request): JsonResponse|BinaryFileResponse|StreamedResponse|Response
    {
        $this->authorize('viewMonthlyReport', Attendance::class);

        $report = $this->attendanceService->generateMonthlyReport($request->user(), $request->validated());

        $format = strtolower((string) $request->input('format', ''));

        if ($format !== '') {
            return $this->respondWithFormattedReport($report, $format);
        }

        return $this->sendResponse([
            'filters' => $report['filters'],
            'summary' => $report['summary'],
            'daily_totals' => $report['daily_totals'],
            'records' => AttendanceResource::collection($report['records']),
        ], 'Monthly attendance report generated');
    }

    /**
     * Provide today's dashboard summary (role-aware).
     */
    public function dashboardSummary(): JsonResponse
    {
        $this->authorize('viewDashboard', Attendance::class);

        $summary = $this->attendanceService->getTodayDashboardSummary(request()->user());

        return $this->sendResponse($summary, 'Dashboard summary');
    }

    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}, summary: array{total_records:int,totals_by_status: array<string,int>}, daily_totals: array<string,int>, records: mixed}  $report
     */
    private function respondWithFormattedReport(array $report, string $format): JsonResponse|BinaryFileResponse|StreamedResponse|Response
    {
        $filename = $this->buildReportFilename($report);

        return match ($format) {
            'excel' => Excel::download(new MonthlyAttendanceExport($report), $filename.'.xlsx'),
            'csv' => $this->streamCsvReport($report, $filename),
            'json' => $this->downloadJsonReport($report, $filename),
            'pdf' => $this->downloadPdfReport($report, $filename),
            default => $this->sendError('Unsupported export format requested.', [], 422),
        };
    }

    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}, summary: array{total_records:int,totals_by_status: array<string,int>}, daily_totals: array<string,int>, records: mixed}  $report
     */
    private function streamCsvReport(array $report, string $filename): StreamedResponse
    {
        $csv = MonthlyAttendanceReportFormatter::toCsv($report);

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename.'.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}, summary: array{total_records:int,totals_by_status: array<string,int>}, daily_totals: array<string,int>, records: mixed}  $report
     */
    private function downloadJsonReport(array $report, string $filename): JsonResponse
    {
        return response()
            ->json(MonthlyAttendanceReportFormatter::toArray($report))
            ->withHeaders([
                'Content-Disposition' => sprintf('attachment; filename="%s.json"', $filename),
            ]);
    }

    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}, summary: array{total_records:int,totals_by_status: array<string,int>}, daily_totals: array<string,int>, records: mixed}  $report
     */
    private function downloadPdfReport(array $report, string $filename): StreamedResponse
    {
        $structured = MonthlyAttendanceReportFormatter::toArray($report);

        $pdf = Pdf::loadView('reports.attendance.monthly', [
            'report' => $report,
            'table' => $structured,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $output = $pdf->output();

        return response()->streamDownload(
            static function () use ($output): void {
                echo $output;
            },
            $filename.'.pdf',
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    /**
     * @param  array{filters: array{month:string,class_name:?string,section:?string}}  $report
     */
    private function buildReportFilename(array $report): string
    {
        $parts = [
            'attendance-report',
            $report['filters']['month'] ?? now()->format('Y-m'),
        ];

        if (! empty($report['filters']['class_name'])) {
            $parts[] = Str::slug($report['filters']['class_name']);
        }

        if (! empty($report['filters']['section'])) {
            $parts[] = Str::slug($report['filters']['section']);
        }

        return implode('-', array_filter($parts));
    }
}
