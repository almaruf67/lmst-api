<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Requests\Student\ClassRosterRequest;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\Student\StudentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;
use Illuminate\Http\Request;

class StudentController extends BaseController
{
    public function __construct(private readonly StudentService $studentService) {}

    /**
     * Display a listing of the students.
     */
    public function index(Request $request): LaravelJsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $students = $this->studentService->listForUser(
            $request->user(),
            $request->only(['class_name', 'section', 'search', 'per_page'])
        );

        return $this->sendResponse(
            $this->formatPaginatedStudents($students),
            'Student list retrieved'
        );
    }

    /**
     * Show students for the authenticated teacher.
     */
    public function myStudents(Request $request): LaravelJsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isTeacher()) {
            return $this->sendError('Forbidden', [], 403);
        }

        $students = $this->studentService->listForUser(
            $user,
            $request->only(['search', 'per_page'])
        );

        return $this->sendResponse(
            $this->formatPaginatedStudents($students),
            'My students retrieved'
        );
    }

    /**
     * Return a full class roster for attendance interfaces.
     */
    public function classRoster(ClassRosterRequest $request): LaravelJsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $className = $validated['class_name'] ?? null;
        $section = $validated['section'] ?? null;

        if ($user && $user->isTeacher()) {
            $className = $user->class_name;
            $section = $user->section;
        }

        if ($className === null) {
            return $this->sendError('Class assignment is required to load a roster.', [], 422);
        }

        $students = $this->studentService->getStudentsByClass($className, $section);

        return $this->sendResponse([
            'class_name' => $className,
            'section' => $section,
            'students' => StudentResource::collection($students),
        ], 'Class roster retrieved');
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): LaravelJsonResponse
    {
        $student = $this->studentService->create($request->user(), $request->validated());

        return $this->sendResponse(new StudentResource($student), 'Student created', 201);
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): LaravelJsonResponse
    {
        $this->authorize('view', $student);

        return $this->sendResponse(new StudentResource($student->load('primaryTeacher')), 'Student detail');
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): LaravelJsonResponse
    {
        $student = $this->studentService->update($student, $request->user(), $request->validated());

        return $this->sendResponse(new StudentResource($student), 'Student updated');
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student): LaravelJsonResponse
    {
        $this->authorize('delete', $student);

        $this->studentService->delete($student);

        return $this->sendResponse(null, 'Student deleted');
    }

    /**
     * Build a standardized payload for paginated resources.
     *
     * @return array<string, mixed>
     */
    private function formatPaginatedStudents(LengthAwarePaginator $students): array
    {
        $resource = StudentResource::collection($students)->response()->getData(true);

        return [
            'students' => $resource['data'] ?? [],
            'meta' => $resource['meta'] ?? [],
            'links' => $resource['links'] ?? [],
        ];
    }
}
