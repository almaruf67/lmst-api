<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Requests\Attendance\AttendanceReportRequest;
use App\Http\Requests\Attendance\BulkAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\JsonResponse;

class AttendanceController extends BaseController
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    /**
     * Handle bulk attendance recording requests.
     */
    public function recordBulk(BulkAttendanceRequest $request): JsonResponse
    {
        $attendances = $this->attendanceService->recordBulk($request->user(), $request->validated());

        return $this->sendResponse([
            'attendances' => AttendanceResource::collection($attendances),
        ], 'Attendance recorded successfully');
    }

    /**
     * Provide a monthly attendance report.
     */
    public function monthlyReport(AttendanceReportRequest $request): JsonResponse
    {
        $report = $this->attendanceService->generateMonthlyReport($request->user(), $request->validated());

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
        $summary = $this->attendanceService->getTodayDashboardSummary(request()->user());

        return $this->sendResponse($summary, 'Dashboard summary');
    }
}
