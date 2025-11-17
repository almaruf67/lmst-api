<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Enums\UserType;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminUserController extends BaseController
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $admins = $this->userService->paginateByType(UserType::Admin, $request->only(['search', 'per_page']));

        return $this->sendResponse($this->formatPaginatedResponse($admins), 'Admin list retrieved');
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        try {
            $admin = $this->userService->createAdmin($request->validated());

            return $this->sendResponse(new UserResource($admin), 'Admin created', 201);
        } catch (Throwable $e) {
            Log::error('Failed to create admin', ['error' => $e->getMessage()]);

            return $this->sendError('Unable to create admin', ['error' => 'Server error'], 500);
        }
    }

    public function show(User $admin): JsonResponse
    {
        $admin = $this->ensureAdmin($admin);
        $this->authorize('view', $admin);

        return $this->sendResponse(new UserResource($admin), 'Admin retrieved');
    }

    public function update(UpdateAdminRequest $request, User $admin): JsonResponse
    {
        $admin = $this->ensureAdmin($admin);
        $this->authorize('update', $admin);

        try {
            $updated = $this->userService->updateAdmin($admin, $request->validated());

            return $this->sendResponse(new UserResource($updated), 'Admin updated');
        } catch (Throwable $e) {
            Log::error('Failed to update admin', ['admin_id' => $admin->id, 'error' => $e->getMessage()]);

            return $this->sendError('Unable to update admin', ['error' => 'Server error'], 500);
        }
    }

    public function destroy(User $admin): JsonResponse
    {
        $admin = $this->ensureAdmin($admin);
        $this->authorize('delete', $admin);

        $requestUser = request()->user();

        if ($requestUser instanceof User && $admin->is($requestUser)) {
            return $this->sendError('You cannot delete your own account.', [], 422);
        }

        try {
            $this->userService->delete($admin);

            return $this->sendResponse(null, 'Admin deleted');
        } catch (Throwable $e) {
            Log::error('Failed to delete admin', ['admin_id' => $admin->id, 'error' => $e->getMessage()]);

            return $this->sendError('Unable to delete admin', ['error' => 'Server error'], 500);
        }
    }

    private function ensureAdmin(User $user): User
    {
        abort_unless($user->user_type === UserType::Admin, 404);

        return $user;
    }

    /**
     * @return array{users: array<mixed>, meta: array<string, mixed>, links: array<string, mixed>}
     */
    private function formatPaginatedResponse(LengthAwarePaginator $paginator): array
    {
        $resource = UserResource::collection($paginator)->response()->getData(true);

        return [
            'users' => $resource['data'] ?? [],
            'meta' => $resource['meta'] ?? [],
            'links' => $resource['links'] ?? [],
        ];
    }
}
