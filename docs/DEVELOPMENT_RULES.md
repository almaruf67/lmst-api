# Development Rules & Guidelines

**Project**: LMST API  
**Critical**: These rules MUST be followed strictly during implementation  
**Last Updated**: November 15, 2025

---

## 🚨 CRITICAL RULES - NO EXCEPTIONS

### 1. NO DOCUMENTATION FILES DURING DEVELOPMENT
**STRICTLY FORBIDDEN to create ANY `.md` files during coding phase:**
- ❌ No summary files
- ❌ No progress tracking files
- ❌ No implementation notes
- ❌ No feature documentation
- ❌ No API documentation

**Exception**: Only `AI_WORKFLOW.md` at the very end (Phase 5 only)

**Why**: Documentation distracts from implementation. All documentation exists in `/docs` folder already.

---

### 2. MCP SERVERS - MANDATORY USAGE

#### Laravel Boost (Primary Tool)
**MUST use `search-docs` before implementing ANY feature:**

```bash
# Examples of REQUIRED usage:

# Before authentication
search-docs queries: ["sanctum authentication", "token refresh", "stateful tokens"]

# Before policies
search-docs queries: ["policies authorization", "resource authorization"]

# Before validation
search-docs queries: ["form requests", "validation rules", "custom messages"]

# Before relationships
search-docs queries: ["eloquent relationships", "eager loading", "n+1 prevention"]

# Before testing
search-docs queries: ["pest testing", "feature tests", "database testing"]
```

**Pattern**: Always search FIRST, implement SECOND.

#### Context7 (For Non-Laravel Packages)
**Use when working with third-party packages:**

```bash
# Intervention Image
mcp_context7_resolve-library-id "intervention/image"
mcp_context7_get-library-docs "/intervention/image" "resize optimize"

# Spatie Sluggable
mcp_context7_resolve-library-id "spatie/laravel-sluggable"
mcp_context7_get-library-docs "/spatie/laravel-sluggable" "slug generation"
```

#### Sequential Thinking (For Complex Problems)
**Use for architectural decisions and complex algorithms:**

```bash
# Use cases:
- Designing bulk attendance recording algorithm
- Optimizing monthly report queries with multiple filters
- Planning cache invalidation strategy
- Debugging complex N+1 query issues
- Designing efficient photo storage structure
```

**When to use**: If a task requires > 3 steps of logical reasoning, use Sequential Thinking FIRST.

---

### 3. PHP CODING STANDARDS - ENFORCED

#### Strict Type Declarations
**MANDATORY on EVERY file:**
```php
<?php

declare(strict_types=1);

namespace App\Services;
```

#### Type Hints - NO EXCEPTIONS
```php
// ✅ CORRECT
public function createStudent(array $data): Student
{
    return Student::create($data);
}

// ❌ WRONG - Missing return type
public function createStudent(array $data)
{
    return Student::create($data);
}

// ❌ WRONG - Missing parameter type
public function createStudent($data): Student
{
    return Student::create($data);
}
```

#### PHPDoc Blocks - REQUIRED
**Every public method MUST have PHPDoc:**
```php
/**
 * Create a new student with photo upload
 *
 * @param array{name: string, student_id: string, class: string} $data Student data
 * @param \Illuminate\Http\UploadedFile|null $photo Optional photo file
 * @return Student Created student model
 * @throws \Illuminate\Validation\ValidationException If validation fails
 * @context Use this for student registration flow
 * @pattern Service layer with transaction and file upload
 */
public function createStudent(array $data, ?UploadedFile $photo = null): Student
{
    // Implementation
}
```

**Required tags**: `@param`, `@return`, `@throws`, `@context`, `@pattern`

#### Constructor Property Promotion
**Always use PHP 8+ syntax:**
```php
// ✅ CORRECT
public function __construct(
    private readonly StudentService $studentService,
    private readonly AttendanceService $attendanceService
) {}

// ❌ WRONG - Old style
private $studentService;

public function __construct(StudentService $studentService)
{
    $this->studentService = $studentService;
}
```

---

### 4. LARAVEL CONVENTIONS - STRICT ADHERENCE

#### Use Artisan Commands
**ALWAYS generate files with artisan:**
```bash
# Models
php artisan make:model Student -mfs  # model + migration + factory + seeder

# Controllers
php artisan make:controller StudentController --api

# Requests
php artisan make:request StoreStudentRequest

# Resources
php artisan make:resource StudentResource

# Policies
php artisan make:policy StudentPolicy --model=Student

# Tests
php artisan make:test StudentTest --pest

# Services (manually create in app/Services/)
```

#### Eloquent Over Query Builder
```php
// ✅ CORRECT
$students = Student::query()
    ->where('class', $class)
    ->with('attendances')
    ->get();

// ❌ WRONG - Using DB facade
$students = DB::table('students')
    ->where('class', $class)
    ->get();
```

#### Named Routes
```php
// ✅ CORRECT
Route::get('/students/{student}', [StudentController::class, 'show'])
    ->name('students.show');

// In controller
return redirect()->route('students.show', $student);

// ❌ WRONG - Hardcoded URLs
return redirect('/students/' . $student->id);
```

#### Config Over Env
```php
// ✅ CORRECT
$tokenExpiry = config('sanctum.access_token_expiry', 60);

// ❌ WRONG - Direct env() call outside config files
$tokenExpiry = env('SANCTUM_ACCESS_TOKEN_EXPIRY', 60);
```

---

### 5. RESPONSE FORMAT - STRICT CONSISTENCY

#### BaseController Methods ONLY
**In controllers, ALWAYS use:**
```php
// Success response
return $this->sendResponse($student, 'Student created successfully', 201);

// Error response
return $this->sendError('Validation failed', $errors, 422);
```

**NEVER return raw responses:**
```php
// ❌ WRONG
return response()->json(['data' => $student]);

// ❌ WRONG
return ['success' => true, 'data' => $student];
```

#### Consistent Response Structure
**ALL responses MUST follow:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "code": 200
}
```

---

### 6. TESTING - NON-NEGOTIABLE

#### Test-Driven Development Flow
```bash
1. Write failing test
2. Implement feature
3. Run test: php artisan test --filter=TestName
4. Refactor if needed
5. Run test again
6. Move to next feature
```

#### Pest Syntax ONLY
```php
// ✅ CORRECT
it('allows admin to create student', function () {
    $admin = User::factory()->admin()->create();
    
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/students', [
            'name' => 'John Doe',
            'student_id' => 'STD001',
            'class' => 'Class 1',
            'section' => 'A',
        ]);
    
    $response->assertCreated();
    expect($response->json('data.name'))->toBe('John Doe');
});

// ❌ WRONG - PHPUnit syntax
public function test_admin_can_create_student() { }
```

#### Coverage Requirements
**Minimum test coverage:**
- All API endpoints: Feature test
- All service methods: Unit test
- All policies: Feature test
- Critical traits: Unit test

---

### 7. DATABASE - BEST PRACTICES

#### Migrations - Reversible
```php
public function up(): void
{
    Schema::create('students', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        // ... all columns
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('students'); // REQUIRED
}
```

#### N+1 Prevention - MANDATORY
```php
// ✅ CORRECT - Eager loading
public function index(): JsonResponse
{
    $students = Student::with(['attendances', 'class'])->get();
    return $this->sendResponse(StudentResource::collection($students));
}

// ❌ WRONG - Lazy loading (N+1 queries)
public function index(): JsonResponse
{
    $students = Student::all(); // Will cause N+1 when accessing attendances
    return $this->sendResponse(StudentResource::collection($students));
}
```

#### Transactions for Multi-Step Operations
```php
// ✅ CORRECT
public function createStudent(array $data): Student
{
    return DB::transaction(function () use ($data) {
        $student = Student::create($data);
        $student->assignToClass($data['class']);
        return $student;
    });
}
```

---

### 8. AUTHORIZATION - POLICY-BASED ONLY

#### Controller Authorization
```php
// ✅ CORRECT - Using policies
public function update(UpdateStudentRequest $request, Student $student): JsonResponse
{
    $this->authorize('update', $student);
    
    $updated = $this->studentService->updateStudent($student, $request->validated());
    return $this->sendResponse(new StudentResource($updated), 'Student updated');
}

// ❌ WRONG - Manual checks
public function update(UpdateStudentRequest $request, Student $student): JsonResponse
{
    if (auth()->user()->user_type !== 'admin') {
        abort(403);
    }
    // ...
}
```

#### Policy Implementation
```php
public function update(User $user, Student $student): bool
{
    // Simple role check - no permission enums!
    if ($user->user_type === 'admin') {
        return true;
    }
    
    // Teacher can only update their class students
    if ($user->user_type === 'teacher') {
        return $student->class === $user->class 
            && $student->section === $user->section;
    }
    
    return false;
}
```

---

### 9. SERVICE LAYER - MANDATORY PATTERN

#### Controllers: Thin
```php
// ✅ CORRECT - Controller delegates to service
public function store(StoreStudentRequest $request): JsonResponse
{
    $student = $this->studentService->createStudent(
        $request->validated(),
        $request->file('photo')
    );
    
    return $this->sendResponse(
        new StudentResource($student),
        'Student created successfully',
        201
    );
}
```

#### Services: Business Logic
```php
// ✅ CORRECT - Service handles complex logic
public function createStudent(array $data, ?UploadedFile $photo = null): Student
{
    return DB::transaction(function () use ($data, $photo) {
        // Handle photo upload
        if ($photo) {
            $data['photo'] = $this->uploadPhoto($photo);
        }
        
        // Create student
        $student = Student::create($data);
        
        // Generate slug
        $student->generateSlug();
        
        // Dispatch event
        event(new StudentCreated($student));
        
        return $student;
    });
}
```

---

### 10. FILE UPLOAD - IMAGE OPTIMIZABLE TRAIT

#### ALWAYS Use the Trait
```php
use App\Traits\ImageOptimizable;

class Student extends Model
{
    use ImageOptimizable;
    
    // In service
    public function updatePhoto(Student $student, UploadedFile $file): Student
    {
        $filename = $student->replaceImage(
            $file,
            $student->photo, // Old filename
            'students',      // Directory
            500,             // Max width
            500,             // Max height
            85               // Quality
        );
        
        $student->photo = $filename;
        $student->save();
        
        return $student;
    }
}
```

---

### 11. CACHING - REDIS STRATEGY

#### User-Specific Cache Keys
```php
// ✅ CORRECT - User-specific key
$stats = Cache::remember(
    "dashboard:{$user->user_type}:{$user->id}",
    300, // 5 minutes
    fn() => $this->calculateDashboardStats($user)
);

// ❌ WRONG - Global key (same for all users)
$stats = Cache::remember('dashboard', 300, fn() => ...);
```

#### Cache Invalidation
```php
// When attendance is recorded
Cache::forget("dashboard:{$user->user_type}:{$user->id}");
Cache::tags(['attendance', "class:{$student->class}"])->flush();
```

---

### 12. ERROR HANDLING - GLOBAL EXCEPTIONS

#### Let Bootstrap Handle It
```php
// ✅ CORRECT - Just throw, bootstrap catches
public function show(Student $student): JsonResponse
{
    $this->authorize('view', $student);
    
    // If not found, ModelNotFoundException is thrown automatically
    // Bootstrap converts it to 404 JSON response
    return $this->sendResponse(new StudentResource($student));
}

// ❌ WRONG - Manual try-catch
public function show(Student $student): JsonResponse
{
    try {
        $this->authorize('view', $student);
        return $this->sendResponse(new StudentResource($student));
    } catch (Exception $e) {
        return $this->sendError($e->getMessage(), [], 500);
    }
}
```

---

### 13. CODE QUALITY - ENFORCED CHECKS

#### Before Every Commit
```bash
# 1. Format code
vendor/bin/pint --dirty

# 2. Run tests
php artisan test

# 3. Check for errors
# (Let your IDE handle this in real-time)
```

#### Pre-Commit Checklist
- [ ] All new methods have PHPDoc blocks
- [ ] All parameters have type hints
- [ ] All methods have return types
- [ ] Used eager loading to prevent N+1
- [ ] Added feature/unit tests for new code
- [ ] Ran Pint formatting
- [ ] All tests passing

---

### 14. GIT COMMIT MESSAGES - SEMANTIC FORMAT

#### Commit Message Structure
```
<type>(<scope>): <subject>

<body>

<footer>
```

#### Types
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code restructuring
- `test`: Adding tests
- `docs`: Documentation only
- `chore`: Maintenance tasks

#### Examples
```bash
feat(auth): implement Sanctum token authentication

- Add SanctumTokenService for token issuance
- Implement refresh token rotation
- Add login/logout endpoints with tests

Closes #1

---

feat(students): add photo upload with UUID optimization

- Implement ImageOptimizable trait
- Add validation for image uploads
- Store photos in flat UUID structure

---

fix(attendance): prevent duplicate attendance records

- Add unique constraint on student_id + date
- Update service to use updateOrCreate
- Add test for duplicate prevention
```

---

### 15. DEBUGGING - TOOLS & STRATEGIES

#### Use Laravel Boost Tools
```bash
# Debug query
Use: tinker tool to run: Student::with('attendances')->first()

# Check database
Use: database-query tool to run: SELECT * FROM students LIMIT 5

# Read logs
Use: browser-logs tool (if frontend issues)
```

#### Logging Best Practices
```php
// In services
Log::info('Creating student', ['data' => $data]);
Log::error('Failed to upload photo', ['error' => $e->getMessage()]);

// In development
dd($variable);        // Dump and die
dump($variable);      // Dump and continue
```

---

## ⚠️ COMMON MISTAKES TO AVOID

### 1. Creating Documentation Files
**Don't create**: README updates, API docs, feature lists during coding

### 2. Skipping Laravel Boost Search
**Don't implement**: Any Laravel feature without searching docs first

### 3. Manual Authorization
**Don't write**: `if ($user->role === 'admin')` in controllers - use policies!

### 4. Inline Validation
**Don't validate**: In controllers - always use FormRequest classes

### 5. Raw Responses
**Don't return**: `response()->json()` - use `sendResponse()`/`sendError()`

### 6. N+1 Queries
**Don't forget**: `->with()` when accessing relationships

### 7. Env Calls Outside Config
**Don't use**: `env('KEY')` anywhere except config files

### 8. Missing Type Hints
**Don't skip**: Parameter and return type declarations

---

## 🎯 EFFICIENCY TIPS

### 1. Parallel Tasks
When possible, run independent tasks in parallel:
```bash
# Terminal 1: Run tests in watch mode
php artisan test --watch

# Terminal 2: Keep dev server running
php artisan serve

# Terminal 3: Run queue workers
php artisan queue:work
```

### 2. IDE Shortcuts
Set up snippets for common patterns:
- `controller` → Full controller template
- `service` → Service class template
- `test` → Pest test template

### 3. Database Reset
Quick reset during development:
```bash
php artisan migrate:fresh --seed
```

---

## 📊 SUCCESS METRICS

### Code Quality
- ✅ All files have `declare(strict_types=1)`
- ✅ All methods have type hints
- ✅ All public methods have PHPDoc
- ✅ Zero Pint formatting issues

### Testing
- ✅ All endpoints have feature tests
- ✅ All services have unit tests
- ✅ All tests passing
- ✅ No skipped tests

### Performance
- ✅ No N+1 queries (check with Debugbar)
- ✅ Dashboard loads < 500ms
- ✅ Bulk attendance < 1s for 30 students
- ✅ Photo uploads optimized to ~200KB

---

**Remember**: These rules exist to maintain consistency, quality, and speed. Follow them strictly, and development will be fast and clean. Ignore them, and you'll waste time refactoring.
