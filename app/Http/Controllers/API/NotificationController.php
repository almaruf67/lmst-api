<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Requests\Notification\NotificationBulkReadRequest;
use App\Http\Requests\Notification\NotificationIndexRequest;
use App\Http\Requests\Notification\NotificationRecentRequest;
use App\Http\Resources\NotificationResource;
use App\Models\AppNotification;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends BaseController
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(NotificationIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);
        $filters = collect($validated)->except('per_page')->toArray();

        $paginator = $this->notificationService->listForUser(
            $request->user(),
            $filters,
            $perPage
        );

        return $this->sendResponse([
            'data' => NotificationResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ], 'Notifications retrieved successfully');
    }

    public function recent(NotificationRecentRequest $request): JsonResponse
    {
        $limit = (int) ($request->validated('limit') ?? 5);
        $notifications = $this->notificationService->recentForUser($request->user(), $limit);

        return $this->sendResponse(
            NotificationResource::collection($notifications),
            'Recent notifications retrieved successfully'
        );
    }

    public function counts(): JsonResponse
    {
        $counts = $this->notificationService->countsForUser(request()->user());

        return $this->sendResponse($counts, 'Notification counts retrieved successfully');
    }

    public function mark(AppNotification $notification): JsonResponse
    {
        $user = request()->user();

        if ($notification->user_id !== $user?->getKey()) {
            return $this->sendError('Notification not found', [], 404);
        }

        $this->notificationService->markAsRead($user, $notification);

        return $this->sendResponse(
            new NotificationResource($notification->fresh()),
            'Notification marked as read'
        );
    }

    public function markSelected(NotificationBulkReadRequest $request): JsonResponse
    {
        $updated = $this->notificationService->markSelectedAsRead(
            $request->user(),
            $request->validated('ids')
        );

        return $this->sendResponse([
            'updated' => $updated,
        ], 'Selected notifications marked as read');
    }

    public function markAll(): JsonResponse
    {
        $updated = $this->notificationService->markAllAsRead(request()->user());

        return $this->sendResponse([
            'updated' => $updated,
        ], 'All notifications marked as read');
    }
}
