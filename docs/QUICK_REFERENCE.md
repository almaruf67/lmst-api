# Quick Reference Card - LMST API Development

**⚡ Print this and keep beside you while coding ⚡**

---

## 🎯 GOLDEN RULES

1. **ALWAYS** use MCP servers before coding: `@mcp_laravel-boost_search-docs`
2. **ALWAYS** extend `BaseController` (not `Controller`)
3. **ALWAYS** use `sendResponse()` / `sendError()` (never return raw arrays)
4. **ALWAYS** use service layer for business logic
5. **ALWAYS** use DB transactions for multi-step operations
6. **ALWAYS** add PHPDoc with `@context` and `@pattern`

---

## 📁 File Creation Commands

```bash
# Controller (API)
php artisan make:controller StudentController --api --no-interaction

# Service (Manual)
touch app/Services/Student/StudentService.php

# Form Request
php artisan make:request StoreStudentRequest --no-interaction

# Model with migration
php artisan make:model Student -m --no-interaction

# Policy
php artisan make:policy StudentPolicy --model=Student --no-interaction

# Test
php artisan make:test Student/StudentTest --pest --no-interaction
```

---

## 🏗️ Code Templates

### Controller Template (Copy-Paste)

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\StoreStudentRequest;
use App\Services\Student\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StudentController extends BaseController
{
    public function __construct(
        private readonly StudentService $studentService
    ) {}

    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $student = $this->studentService->createStudent(
                $request->validated(),
                $request->file('photo')
            );

            return $this->sendResponse($student, 'Student created successfully.', 201);
        } catch (\Throwable $exception) {
            Log::error('Failed to create student', [
                'data' => $request->validated(),
                'error' => $exception->getMessage(),
            ]);

            return $this->sendError(
                'Failed to create student.',
                ['error' => $exception->getMessage()],
                500
            );
        }
    }
}
```

### Service Template (Copy-Paste)

```php
<?php

namespace App\Services\Student;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentService
{
    public function createStudent(array $data, ?UploadedFile $photo = null): Student
    {
        DB::beginTransaction();

        try {
            if ($photo) {
                $data['photo'] = (new Student())->uploadAndOptimizeImage(
                    $photo,
                    'students',
                    500,
                    500,
                    85
                );
            }

            $student = Student::create($data);
            $student->load(['createdBy:id,first_name,last_name']);

            DB::commit();

            Log::info('Student created', [
                'student_id' => $student->id,
                'name' => $student->name,
            ]);

            return $student;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Student creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }
}
```

### FormRequest Template (Copy-Paste)

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy handles authorization
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'student_id' => ['required', 'string', 'unique:students'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Student name is required',
            'student_id.unique' => 'This student ID is already registered',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'data' => $validator->errors(),
                'version' => 'v1',
            ], 422)
        );
    }
}
```

### Policy Template (Copy-Paste)

```php
<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->user_type === 'admin' || $user->user_type === 'teacher';
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'admin';
    }

    public function update(User $user, Student $student): bool
    {
        return $user->user_type === 'admin';
    }
}
```

### Test Template (Copy-Paste)

```php
<?php

use App\Models\User;
use App\Models\Student;

it('allows admin to create student', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    // Act
    $response = $this->postJson('/api/students', [
        'name' => 'John Doe',
        'student_id' => 'STU001',
        'class' => '5',
        'section' => 'A',
    ]);

    // Assert
    $response->assertSuccessful();
    $this->assertDatabaseHas('students', ['student_id' => 'STU001']);
});
```

---

## 🔍 MCP Server Cheatsheet

```bash
# BEFORE implementing feature
@mcp_laravel-boost_search-docs queries: ["feature-name", "related-topic"]

# Package documentation
@mcp_context7_resolve-library-id intervention/image
@mcp_context7_get-library-docs /intervention/image "resize images"

# Complex problems
@mcp_sequentialthinking "Design bulk attendance recording algorithm"
```

**Common Searches**:
- Authentication: `["sanctum authentication", "personal access tokens"]`
- Authorization: `["policies", "authorization"]`
- Validation: `["form request validation"]`
- Relationships: `["eloquent relationships", "eager loading"]`
- Testing: `["pest testing", "feature tests"]`

---

## ✅ Pre-Commit Checklist

- [ ] Used `search-docs` before implementing?
- [ ] Extends `BaseController`?
- [ ] Using `sendResponse()` / `sendError()`?
- [ ] Service layer for business logic?
- [ ] DB transactions?
- [ ] Type hints on all parameters?
- [ ] PHPDoc with `@context` and `@pattern`?
- [ ] Eager loading (`->with()`) to prevent N+1?
- [ ] Error logging with context?
- [ ] Test written?
- [ ] Run: `vendor/bin/pint --dirty`
- [ ] Run: `php artisan test --filter=FeatureName`

---

## 📦 Model Traits

```php
use App\Traits\Loggable;           // Auto-tracks created_by, updated_by
use App\Traits\ImageOptimizable;    // Photo upload with UUID
use Spatie\Sluggable\HasSlug;       // Auto-generates slug
```

**In Model**:
```php
class Student extends Model
{
    use Loggable, ImageOptimizable, HasSlug;
    
    // Relationships with return types
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
```

---

## 🚨 Common Mistakes

❌ **DON'T**:
```php
// Don't return raw arrays
return response()->json(['data' => $student]);

// Don't skip type hints
function create($data)

// Don't skip transactions
$student = Student::create($data);
$attendance = Attendance::create($data);

// Don't manual permission checks
if ($user->permission === 'admin')
```

✅ **DO**:
```php
// Use BaseController methods
return $this->sendResponse($student, 'Created', 201);

// Always type hint
function create(array $data): Student

// Use transactions
DB::transaction(function() use ($data) {
    $student = Student::create($data);
    $attendance = Attendance::create($data);
});

// Use policies
if ($user->user_type === 'admin')
```

---

## 🎯 Response Format (From JsonResponse)

**Success**:
```json
{
    "message": "Student created successfully",
    "data": { ... },
    "version": "v1"
}
```

**Error**:
```json
{
    "message": "Validation failed",
    "data": { "name": ["Name is required"] },
    "version": "v1"
}
```

**HTTP Codes**:
- 200: OK (list, show, update)
- 201: Created (store)
- 204: No Content (delete)
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

---

## 📞 Quick Help

**Stuck?** Follow this order:
1. Check `CODING_CONVENTIONS.md`
2. Search docs: `@mcp_laravel-boost_search-docs`
3. Check lara-api-starter similar file
4. Use Sequential Thinking for complex problems

**Files to Reference**:
- `docs/CODING_CONVENTIONS.md` - Your exact patterns
- `docs/ARCHITECTURE.md` - System design decisions
- `docs/DEVELOPMENT_RULES.md` - Strict rules
- `docs/MCP_SERVERS_GUIDE.md` - MCP usage examples

---

**Remember**: MCP servers FIRST, then code. No exceptions! 🚀
