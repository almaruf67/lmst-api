<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use App\Support\ClassroomOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeacherController extends BaseController
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $teachers = $this->userService->paginateByType(
            UserType::Teacher,
            $request->only(['search', 'per_page', 'class_name', 'section'])
        );

        return $this->sendResponse(
            $this->formatPaginatedResponse($teachers),
            'Teacher list retrieved'
        );
    }

    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        try {
            $teacher = $this->userService->createTeacher($request->validated());

            return $this->sendResponse(new UserResource($teacher), 'Teacher created', 201);
        } catch (Throwable $e) {
            Log::error('Failed to create teacher', ['error' => $e->getMessage()]);

            return $this->sendError('Unable to create teacher', ['error' => 'Server error'], 500);
        }
    }

    public function show(User $teacher): JsonResponse
    {
        $teacher = $this->ensureTeacher($teacher);
        $this->authorize('view', $teacher);

        return $this->sendResponse(new UserResource($teacher), 'Teacher retrieved');
    }

    public function update(UpdateTeacherRequest $request, User $teacher): JsonResponse
    {
        $teacher = $this->ensureTeacher($teacher);
        $this->authorize('update', $teacher);

        try {
            $updated = $this->userService->updateTeacher($teacher, $request->validated());

            return $this->sendResponse(new UserResource($updated), 'Teacher updated');
        } catch (Throwable $e) {
            Log::error('Failed to update teacher', ['teacher_id' => $teacher->id, 'error' => $e->getMessage()]);

            return $this->sendError('Unable to update teacher', ['error' => 'Server error'], 500);
        }
    }

    public function destroy(User $teacher): JsonResponse
    {
        $teacher = $this->ensureTeacher($teacher);
        $this->authorize('delete', $teacher);

        try {
            $this->userService->delete($teacher);

            return $this->sendResponse(null, 'Teacher deleted');
        } catch (Throwable $e) {
            Log::error('Failed to delete teacher', ['teacher_id' => $teacher->id, 'error' => $e->getMessage()]);

            return $this->sendError('Unable to delete teacher', ['error' => 'Server error'], 500);
        }
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->sendResponse([
            'classes' => ClassroomOptions::classes(),
            'sections' => ClassroomOptions::sections(),
        ], 'Classroom options');
    }

    private function ensureTeacher(User $user): User
    {
        abort_unless($user->user_type === UserType::Teacher, 404);

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
