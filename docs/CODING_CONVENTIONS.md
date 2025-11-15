# LMST API - Coding Conventions

**Reference**: lara-api-starter boilerplate patterns  
**Purpose**: Exact coding standards to follow from your existing boilerplate  
**Last Updated**: November 15, 2025

---

## 🎯 Critical Rules

### 1. **ALWAYS Use MCP Servers Before Coding**

**Pattern from DEVELOPMENT_RULES.md**: Search docs → Implement → Test

```bash
# Before ANY feature implementation:
@mcp_laravel-boost_search-docs queries: ["feature-name", "related-topic"]

# For package-specific questions:
@mcp_context7_resolve-library-id package/name
@mcp_context7_get-library-docs /package/name "feature"

# For complex algorithms:
@mcp_sequentialthinking "Design problem in detail"
```

**Examples**:
- Authentication: `search-docs queries: ["sanctum authentication", "personal access tokens"]`
- Policies: `search-docs queries: ["policies", "authorization", "resource policies"]`
- Relationships: `search-docs queries: ["eloquent relationships", "eager loading"]`

---

## 📁 Directory Structure (From lara-api-starter)

```
app/
├── Http/Controllers/
│   └── API/
│       ├── BaseController.php          # ✅ CRITICAL - All controllers extend this
│       ├── Admin/                      # Admin endpoints
│       │   ├── AuthController.php
│       │   └── UserController.php
│       └── Front/                      # Frontend/Teacher endpoints
├── Services/
│   ├── Auth/
│   │   └── SanctumTokenService.php    # Token generation service
│   ├── Admin/
│   │   ├── AuthService.php            # Business logic for admin auth
│   │   └── UserService.php            # Business logic for users
│   └── Student/
├── Models/
│   ├── User.php
│   └── Student.php
├── Traits/
│   ├── Loggable.php                   # ✅ Audit trail tracking
│   └── ImageOptimizable.php           # ✅ Photo upload handling
├── Lib/
│   └── JsonResponse.php               # ✅ Global response formatter
└── Enums/
    ├── UserType.php
    └── AttendanceStatus.php
```

---

## 🏗️ Architecture Pattern (From lara-api-starter)

### 1. **BaseController Pattern** (MANDATORY)

**Location**: `app/Http/Controllers/API/BaseController.php`

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Lib\JsonResponse;

/**
 * Base API Controller
 *
 * @context All API controllers extend this for consistent response formatting
 * @pattern Uses JsonResponse library for standardized responses
 */
class BaseController extends Controller
{
    /**
     * Send success response
     *
     * @param mixed $result Data to return
     * @param string $message Success message
     * @param int $code HTTP status code (default 200)
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($result, string $message, int $code = 200)
    {
        return JsonResponse::success($result, $message, null, $code);
    }

    /**
     * Send error response
     *
     * @param string $error Error message
     * @param array $errorMessages Validation errors or details
     * @param int $code HTTP status code (default 400)
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError(string $error, array $errorMessages = [], int $code = 400)
    {
        return JsonResponse::error($error, $code, [$code], $errorMessages);
    }

    /**
     * Send warning response
     *
     * @param string $message Warning message
     * @param mixed $result Optional data
     * @param int $code HTTP status code (default 207)
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendWarning(string $message, $result = null, int $code = 207)
    {
        return JsonResponse::warning($result, $message, null, $code);
    }
}
```

### 2. **JsonResponse Utility** (MANDATORY)

**Location**: `app/Lib/JsonResponse.php`

**Key Methods**:
- `JsonResponse::success($data, $message, $to = null, $code = 200, $app_code = null)`
- `JsonResponse::error($message, $code = 400, $allow_code = [], $data = [], $app_code = null)`
- `JsonResponse::warning($data, $message, $to = null, $code = 207, $app_code = null)`

**Response Format**:
```json
{
    "message": "Success message",
    "data": { ... },
    "version": "v1"
}
```

### 3. **Controller Pattern** (From lara-api-starter)

```php
<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\API\Admin\StoreStudentRequest;
use App\Services\Student\StudentService;
use Illuminate\Http\JsonResponse;

/**
 * Student Controller
 *
 * @context Handles student CRUD operations with role-based authorization
 * @pattern Service layer handles business logic, controller handles HTTP
 */
class StudentController extends BaseController
{
    /**
     * Create a new controller instance
     *
     * @pattern Constructor property promotion (PHP 8.3)
     */
    public function __construct(
        private readonly StudentService $studentService
    ) {}

    /**
     * Store a new student
     *
     * @param StoreStudentRequest $request Validated request
     * @return JsonResponse
     */
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

**Key Points**:
- ✅ Extends `BaseController`
- ✅ Constructor property promotion with `private readonly`
- ✅ PHPDoc with `@context` and `@pattern` tags
- ✅ Type hints on all parameters and return types
- ✅ Try-catch with detailed logging
- ✅ Use `sendResponse()` / `sendError()` methods
- ✅ Service layer handles business logic

---

## 🔧 Service Layer Pattern (From lara-api-starter)

### Pattern Structure

```php
<?php

namespace App\Services\Student;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Student Service
 *
 * @context Business logic for student operations
 * @pattern Database transactions, caching, audit logging
 */
class StudentService
{
    /**
     * Create a new student
     *
     * @param array<string, mixed> $data Student data
     * @param UploadedFile|null $photo Optional photo file
     * @return Student Created student instance
     * @throws \Exception
     */
    public function createStudent(array $data, ?UploadedFile $photo = null): Student
    {
        DB::beginTransaction();

        try {
            // Handle photo upload
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
                'created_by' => auth()->id(),
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

    /**
     * Get students with filters
     *
     * @param array<string, mixed> $filters
     * @return Collection
     */
    public function getStudents(array $filters): Collection
    {
        $search = $filters['search'] ?? null;
        $class = $filters['class'] ?? null;
        $section = $filters['section'] ?? null;

        return Student::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->when($class, fn($q, $class) => $q->where('class', $class))
            ->when($section, fn($q, $section) => $q->where('section', $section))
            ->with(['createdBy:id,first_name,last_name'])
            ->latest()
            ->get();
    }
}
```

**Key Points**:
- ✅ Database transactions for multi-step operations
- ✅ Comprehensive error logging with context
- ✅ Eager loading to prevent N+1 queries
- ✅ Type hints with array shapes in PHPDoc
- ✅ `when()` clauses for conditional filters
- ✅ Commit on success, rollback on failure

---

## 🎨 PHPDoc Standards (From lara-api-starter)

### Controller PHPDoc

```php
/**
 * Authenticate an admin user and issue an OAuth access token.
 *
 * Validates admin credentials against the database, verifies user status and type,
 * and issues a Sanctum token with user information, roles, and permissions.
 *
 * Features:
 * - Validates email format and password strength
 * - Checks user type and status
 * - Issues access and refresh tokens
 * - Returns user profile for frontend initialization
 * - Includes audit logging
 *
 * Security considerations:
 * - Available to unauthenticated users (login endpoint)
 * - Rate limiting applied at middleware level
 * - Failed login attempts logged
 *
 * @param LoginRequest $request Validated request with email and password
 * @return JsonResponse Authentication data with tokens or error
 */
public function login(LoginRequest $request): JsonResponse
```

### Service PHPDoc

```php
/**
 * Get paginated list of users with optional filtering.
 *
 * @param array<string, mixed> $filters Associative array with search, status, role_id, etc.
 * @return LengthAwarePaginator Paginated user collection with relations
 */
public function getUsers(array $filters): LengthAwarePaginator
```

**Required Elements**:
- ✅ Short description (one line)
- ✅ Detailed explanation (2-4 paragraphs)
- ✅ Features list (bullet points)
- ✅ Security considerations (for auth-related)
- ✅ `@param` with type and description
- ✅ `@return` with type and description
- ✅ `@throws` if exceptions are thrown
- ✅ `@context` tag (what it's used for)
- ✅ `@pattern` tag (implementation pattern)

---

## 🛡️ Loggable Trait (From lara-api-starter)

**Location**: `app/Traits/Loggable.php`

```php
<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Loggable Trait
 *
 * @context Automatic audit trail tracking for models
 * @pattern Hooks into Eloquent events (creating, updating)
 */
trait Loggable
{
    /**
     * Boot the trait and set up model events
     */
    protected static function bootLoggable(): void
    {
        // Set created_by and created_ip on model creation
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
            $model->created_ip = Request::ip();
        });

        // Set updated_by and updated_ip on model update
        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
            $model->updated_ip = Request::ip();
        });
    }
}
```

**Usage in Models**:
```php
use App\Traits\Loggable;

class Student extends Model
{
    use Loggable;
    
    // Automatically tracks:
    // - created_by (user ID)
    // - created_ip (IP address)
    // - updated_by (user ID)
    // - updated_ip (IP address)
}
```

---

## 🔐 Token Service Pattern (Adapt from PassportTokenService)

### SanctumTokenService (For LMST API)

**Location**: `app/Services/Auth/SanctumTokenService.php`

```php
<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Sanctum Token Service
 *
 * @context Handles Sanctum personal access token generation
 * @pattern Unified response format {success, data, message, code}
 */
class SanctumTokenService
{
    /**
     * Issue access and refresh tokens for a user
     *
     * @param User $user The authenticated user
     * @return array{success: bool, data: array|null, message: string, code: int}
     */
    public function issueToken(User $user): array
    {
        try {
            // Create access token (60 minutes expiry)
            $accessToken = $user->createToken(
                'access_token',
                ['*'],
                now()->addMinutes(config('sanctum.access_token_expiry', 60))
            );

            // Create refresh token (30 days expiry)
            $refreshToken = $user->createToken(
                'refresh_token',
                ['refresh'],
                now()->addMinutes(config('sanctum.refresh_token_expiry', 43200))
            );

            return [
                'success' => true,
                'data' => [
                    'access_token' => $accessToken->plainTextToken,
                    'refresh_token' => $refreshToken->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.access_token_expiry', 60) * 60,
                    'user' => $user->load(['role:id,name']),
                ],
                'message' => 'Token issued successfully',
                'code' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to issue token: ' . $e->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Refresh access token using refresh token
     *
     * @param string $refreshToken The refresh token
     * @return array{success: bool, data: array|null, message: string, code: int}
     */
    public function refreshToken(string $refreshToken): array
    {
        // Implementation following unified response pattern
    }
}
```

**Key Points**:
- ✅ Unified response format (same as PassportTokenService)
- ✅ Type hints with array shapes: `array{success: bool, ...}`
- ✅ Try-catch with detailed error handling
- ✅ Config-driven expiry times
- ✅ Returns user with relations for frontend

---

## 📝 Form Request Pattern (From lara-api-starter)

```php
<?php

namespace App\Http\Requests\API\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Store Student Request
 *
 * @context Validation for student creation
 * @pattern Array-based validation rules with custom messages
 */
class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'student_id' => ['required', 'string', 'max:50', 'unique:students,student_id'],
            'class' => ['required', 'string', 'in:1,2,3,4,5'],
            'section' => ['required', 'string', 'in:A,B,C'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Get custom error messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Student name is required',
            'student_id.required' => 'Student ID is required',
            'student_id.unique' => 'This student ID is already registered',
            'class.in' => 'Class must be between 1 and 5',
            'section.in' => 'Section must be A, B, or C',
            'photo.image' => 'Photo must be an image file',
            'photo.max' => 'Photo size must not exceed 2MB',
        ];
    }

    /**
     * Handle a failed validation attempt
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
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

**Key Points**:
- ✅ Array-based validation rules (not string-based)
- ✅ Custom error messages for user-friendly errors
- ✅ `failedValidation()` override for consistent format
- ✅ Authorization returns `true` (policies handle auth)

---

## 🧪 Testing Pattern (From lara-api-starter + Pest v4)

```php
<?php

use App\Models\User;
use App\Models\Student;

it('allows admin to create student with photo', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $data = [
        'name' => 'John Doe',
        'student_id' => 'STU001',
        'class' => '5',
        'section' => 'A',
    ];

    // Act
    $response = $this->postJson('/api/students', $data);

    // Assert
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'message',
        'data' => ['id', 'name', 'student_id', 'slug'],
        'version',
    ]);

    $this->assertDatabaseHas('students', [
        'name' => 'John Doe',
        'student_id' => 'STU001',
    ]);
});

it('prevents teacher from creating student', function () {
    // Arrange
    $teacher = User::factory()->teacher()->create();
    $this->actingAs($teacher);

    // Act
    $response = $this->postJson('/api/students', [
        'name' => 'Jane Doe',
        'student_id' => 'STU002',
        'class' => '3',
        'section' => 'B',
    ]);

    // Assert
    $response->assertForbidden();
});
```

**Key Points**:
- ✅ Arrange-Act-Assert pattern (explicit comments)
- ✅ Use `actingAs()` for authentication
- ✅ Use `assertSuccessful()` instead of `assertStatus(200)`
- ✅ Use `assertForbidden()` instead of `assertStatus(403)`
- ✅ Use `assertJsonStructure()` for response validation
- ✅ Use `assertDatabaseHas()` for persistence verification

---

## 🚀 Quick Reference Checklist

### Before Writing ANY Code:
- [ ] Run `@mcp_laravel-boost_search-docs` for feature documentation
- [ ] Check lara-api-starter for similar patterns
- [ ] Review ARCHITECTURE.md for system design decisions

### Every Controller:
- [ ] Extends `BaseController`
- [ ] Constructor property promotion: `private readonly ServiceClass`
- [ ] PHPDoc with `@context` and `@pattern`
- [ ] Type hints on all parameters and return types
- [ ] Try-catch with `Log::error()` for exceptions
- [ ] Use `sendResponse()` / `sendError()` methods only

### Every Service:
- [ ] Database transactions for multi-step operations
- [ ] Eager loading with `->with()` to prevent N+1
- [ ] Detailed logging with context arrays
- [ ] Type hints with array shapes: `@param array<string, mixed>`
- [ ] Return type declarations on all methods

### Every Model:
- [ ] Use `Loggable` trait for audit trails
- [ ] Use `ImageOptimizable` trait for photo uploads
- [ ] Use `HasSlug` trait for slugs (Spatie)
- [ ] Define relationships with return types
- [ ] Cast enums properly: `casts()` method

### Every FormRequest:
- [ ] Array-based validation rules
- [ ] Custom error messages in `messages()` method
- [ ] Override `failedValidation()` for consistent format
- [ ] Authorization returns `true` (policies handle auth)

### Every Test:
- [ ] Arrange-Act-Assert pattern
- [ ] Use `actingAs()` for auth
- [ ] Use specific assertions: `assertSuccessful()`, `assertForbidden()`
- [ ] Test both happy path and failure scenarios

---

## 📚 MCP Server Usage Examples (Mandatory)

### Example 1: Before Implementing Authentication

```bash
# Step 1: Search Sanctum docs
@mcp_laravel-boost_search-docs queries: ["sanctum authentication", "personal access tokens", "token refresh"]

# Step 2: Review patterns
# Read search results, understand Sanctum stateful tokens

# Step 3: Implement following BaseController pattern
# Create SanctumTokenService with unified response format
```

### Example 2: Before Implementing Policies

```bash
# Step 1: Search policy docs
@mcp_laravel-boost_search-docs queries: ["policies", "authorization", "resource policies"]

# Step 2: Check lara-api-starter
# Review RolePolicy or similar for pattern

# Step 3: Implement StudentPolicy with simple role checks
```

### Example 3: Complex Algorithm Design

```bash
# Use Sequential Thinking for bulk attendance recording
@mcp_sequentialthinking "Design bulk attendance algorithm:
- Accept array of student records
- Validate no duplicates for same date
- Use database transaction
- Optimize for 30+ students
- Handle partial failures
- Cache invalidation strategy"

# Analyze output, implement based on decision
```

---

## ✅ Final Verification

Before committing any code, verify:

1. **MCP Server Usage**: Used `search-docs` before implementing feature? ✅
2. **BaseController**: Controller extends BaseController? ✅
3. **Response Format**: Using `sendResponse()` / `sendError()`? ✅
4. **Service Layer**: Business logic in service, not controller? ✅
5. **Type Hints**: All parameters and return types declared? ✅
6. **PHPDoc**: All public methods have PHPDoc with `@context` and `@pattern`? ✅
7. **Transactions**: Multi-step operations in DB transactions? ✅
8. **Logging**: Errors logged with context? ✅
9. **N+1 Prevention**: Using eager loading with `->with()`? ✅
10. **Tests**: Pest tests written following Arrange-Act-Assert? ✅

---

**Remember**: This is YOUR coding style from lara-api-starter. Follow it exactly. Use MCP servers BEFORE every implementation. No exceptions.
