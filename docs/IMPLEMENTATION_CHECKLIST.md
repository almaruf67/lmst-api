# Implementation Checklist

**Project**: LMST API  
**Purpose**: Step-by-step implementation tracking  
**Usage**: Check off items as you complete them

---

## ⚠️ BEFORE YOU START

**CRITICAL READING REQUIRED**:

1. **Read `CODING_CONVENTIONS.md`** - Your exact coding patterns from lara-api-starter
   - BaseController pattern (sendResponse/sendError)
   - Service layer structure with transactions
   - PHPDoc standards with @context and @pattern
   - FormRequest validation patterns
   - Loggable trait for audit trails
   - Testing patterns (Arrange-Act-Assert)

2. **MCP Server Usage is MANDATORY**:
   - `@mcp_laravel-boost_search-docs` BEFORE implementing ANY feature
   - `@mcp_context7` for package-specific documentation
   - `@mcp_sequentialthinking` for complex algorithms

3. **Reference lara-api-starter** for patterns:
   - `/var/www/laravel/laravel-modular/lara-api-starter/app/Http/Controllers/API/BaseController.php`
   - `/var/www/laravel/laravel-modular/lara-api-starter/app/Services/Admin/UserService.php`
   - `/var/www/laravel/laravel-modular/lara-api-starter/app/Traits/Loggable.php`

**DO NOT PROCEED** without reading CODING_CONVENTIONS.md first!

---

## Phase 1: Foundation & Authentication (2-3 hours)

### 1.1 Project Setup
- [x] Verify Sanctum installed (`composer.json`)
- [ ] Create directory structure:
  - [x] `app/Services/Auth/`
  - [x] `app/Services/Student/`
  - [x] `app/Services/Attendance/`
  - [x] `app/Traits/`
  - [x] `app/Lib/`
  - [x] `app/Enums/`
- [ ] Run `php artisan storage:link`

### 1.2 Configuration
- [ ] Configure `config/sanctum.php`:
  - [ ] Set stateful domains
  - [x] Configure token expiry
- [ ] Update `.env`:
  - [x] Add `SANCTUM_ACCESS_TOKEN_EXPIRY=60`
  - [x] Add `SANCTUM_REFRESH_TOKEN_EXPIRY=43200`
  - [ ] Configure Redis connection

### 1.3 Base Response Architecture
**MCP**: `search-docs queries: ["api responses", "json responses"]`

- [x] Create `app/Http/Controllers/Controller.php` (BaseController)
  - [x] Add `sendResponse()` method
  - [x] Add `sendError()` method
- [x] Create `app/Lib/JsonResponse.php`
  - [x] Implement response formatting
  - [x] Add status text mapping
- [ ] Update `bootstrap/app.php`:
  - [ ] Configure global exception handling
  - [ ] Register JsonResponse for exceptions

### 1.4 Traits
- [x] ✅ `app/Traits/ImageOptimizable.php` (Already created)
- [x] Create `app/Traits/Loggable.php`:
  - [x] Add `created_by`, `updated_by` tracking
  - [x] Add `created_ip`, `updated_ip` tracking
  - [x] Implement boot method for model events

### 1.5 Authentication Service
**MCP**: `search-docs queries: ["sanctum authentication", "personal access tokens", "token refresh"]`

- [x] Create `app/Services/Auth/SanctumTokenService.php`:
  - [x] `issueToken(User $user): array` method
  - [x] `refreshToken(string $token): array` method
  - [x] `revokeToken(User $user): bool` method
  - [x] Unified response format `{success, data, message, code}`
- [x] Create `app/Services/Auth/AuthService.php`:
  - [x] `login(string $email, string $password): array` method
  - [x] `logout(User $user): array` method
  - [x] `me(User $user): array` method

### 1.6 Authentication Controller
- [x] Run: `php artisan make:controller Auth/AuthController --no-interaction`
- [x] Implement methods:
  - [x] `login(LoginRequest $request)`
  - [x] `refresh(RefreshRequest $request)`
  - [x] `logout(Request $request)`
  - [x] `me(Request $request)`

### 1.7 Authentication Validation
- [x] Run: `php artisan make:request Auth/LoginRequest --no-interaction`
  - [x] Add validation rules (email, password)
  - [x] Add custom error messages
- [x] Run: `php artisan make:request Auth/RefreshRequest --no-interaction`
  - [x] Add validation rules (refresh_token)

### 1.8 Authentication Routes
- [x] Add to `routes/api.php`:
  - [x] `POST /api/login`
  - [x] `POST /api/refresh`
  - [x] `POST /api/logout` (auth:sanctum)
  - [x] `GET /api/me` (auth:sanctum)

### 1.9 Testing - Authentication
- [ ] Run: `php artisan make:test Auth/AuthenticationTest --pest --no-interaction`
- [ ] Write tests:
  - [ ] `it('logs in with valid credentials')`
  - [ ] `it('returns error with invalid credentials')`
  - [ ] `it('refreshes token successfully')`
  - [ ] `it('revokes token on logout')`
  - [ ] `it('returns authenticated user data')`
- [ ] Run: `php artisan test --filter=AuthenticationTest`

---

## Phase 2: Database Schema & Models (2-3 hours)

### 2.1 User Enum
- [x] Create `app/Enums/UserType.php`:
  - [x] `Admin = 'admin'`
  - [x] `Teacher = 'teacher'`

### 2.2 Attendance Status Enum
- [x] Create `app/Enums/AttendanceStatus.php`:
  - [x] `Present = 'present'`
  - [x] `Absent = 'absent'`
  - [x] `Late = 'late'`

### 2.3 Users Migration
**MCP**: `search-docs queries: ["migrations", "enum column", "foreign keys"]`

- [ ] Update existing users migration:
  - [x] Add `user_type` enum column (admin, teacher)
  - [x] Add `class` string nullable
  - [x] Add `section` string nullable
  - [x] Add audit fields (created_by, updated_by, created_ip, updated_ip)
  - [x] Add soft deletes
  - [ ] Add indexes

### 2.4 Students Migration
- [x] Run: `php artisan make:migration create_students_table --no-interaction`
- [x] Define schema:
  - [x] `id`, `name`, `student_id` (unique)
  - [x] `class`, `section`
  - [x] `photo` (nullable, UUID filename)
  - [x] `slug` (unique, nullable)
  - [x] Audit fields (created_by, updated_by, created_ip, updated_ip)
  - [x] Timestamps, soft deletes
  - [x] Indexes: `student_id`, `class`, `section`, `slug`

### 2.5 Attendances Migration
- [x] Run: `php artisan make:migration create_attendances_table --no-interaction`
- [x] Define schema:
  - [x] `id`, `student_id` (foreign), `date`, `status` (enum)
  - [x] `note` (text, nullable)
  - [x] `recorded_by` (foreign to users)
  - [x] Timestamps
  - [x] Unique constraint: `student_id + date`
  - [x] Indexes: `date`, `status`, `student_id`

### 2.6 Run Migrations
- [ ] Run: `php artisan migrate`
- [ ] Verify tables created: `php artisan tinker` → `Schema::hasTable('students')`

### 2.7 User Model
**MCP**: `search-docs queries: ["eloquent models", "relationships", "casts"]`

- [x] Update `app/Models/User.php`:
  - [x] Add fillable fields
  - [x] Cast `user_type` to `UserType` enum
  - [x] Add `Loggable` trait
  - [x] Relationship: `hasMany(Student::class, 'created_by')`
  - [x] Relationship: `hasMany(Attendance::class, 'recorded_by')`
  - [x] Soft deletes

### 2.8 Student Model
- [x] Run: `php artisan make:model Student --no-interaction`
- [x] Configure model:
  - [x] Add fillable fields
  - [x] Use `Loggable` trait
  - [x] Use `ImageOptimizable` trait
  - [x] Use `HasSlug` trait (Spatie)
  - [x] Relationship: `hasMany(Attendance::class)`
  - [x] Relationship: `belongsTo(User::class, 'created_by')`
  - [x] Accessor: `getPhotoUrlAttribute()`
  - [x] Soft deletes

### 2.9 Attendance Model
- [x] Run: `php artisan make:model Attendance --no-interaction`
- [x] Configure model:
  - [x] Add fillable fields
  - [x] Cast `status` to `AttendanceStatus` enum
  - [x] Cast `date` to date
  - [x] Relationship: `belongsTo(Student::class)`
  - [x] Relationship: `belongsTo(User::class, 'recorded_by')`

### 2.10 Factories
**MCP**: `search-docs queries: ["factories", "faker data"]`

- [x] Run: `php artisan make:factory UserFactory --no-interaction`
  - [x] Add state for `admin` type
  - [x] Add state for `teacher` type with class/section
- [x] Run: `php artisan make:factory StudentFactory --no-interaction`
  - [x] Generate realistic student data
  - [x] Random class (1-5), section (A-C)
- [x] Run: `php artisan make:factory AttendanceFactory --no-interaction`
  - [x] Random status, date within last 30 days

### 2.11 Seeders
**MCP**: `search-docs queries: ["database seeding", "seeders"]`

- [ ] Run: `php artisan make:seeder UserSeeder --no-interaction`
  - [ ] Create admin user (admin@test.com / password)
  - [ ] Create 5 teacher users with assigned classes
- [ ] Run: `php artisan make:seeder StudentSeeder --no-interaction`
  - [ ] Create 50 students across different classes
- [ ] Run: `php artisan make:seeder AttendanceSeeder --no-interaction`
  - [ ] Create attendance records for last 7 days
- [x] Update `DatabaseSeeder.php` to call all seeders
- [ ] Run: `php artisan db:seed`

---

## Phase 3: Student Module (3-4 hours)

### 3.1 Student Policy
**MCP**: `search-docs queries: ["policies", "authorization", "resource policies"]`

- [x] Run: `php artisan make:policy StudentPolicy --model=Student --no-interaction`
- [x] Implement methods:
  - [x] `viewAny(User $user)`
  - [x] `view(User $user, Student $student)`
  - [x] `create(User $user)`
  - [x] `update(User $user, Student $student)`
  - [x] `delete(User $user, Student $student)`

### 3.2 Student Service
**MCP**: `mcp_sequentialthinking` for complex CRUD logic

- [x] Create `app/Services/Student/StudentService.php`:
  - [x] `getStudents(User $user, array $filters): Collection`
  - [x] `createStudent(array $data, ?UploadedFile $photo): Student`
  - [x] `updateStudent(Student $student, array $data, ?UploadedFile $photo): Student`
  - [x] `deleteStudent(Student $student): bool`
  - [ ] `getStudentsByClass(string $class, string $section): Collection`

### 3.3 Student Controller
- [x] Run: `php artisan make:controller StudentController --api --no-interaction`
- [x] Inject `StudentService` in constructor
- [x] Implement methods:
  - [x] `index(Request $request)` - List with filters
  - [x] `store(StoreStudentRequest $request)` - Create with photo
  - [x] `show(Student $student)` - View single
  - [x] `update(UpdateStudentRequest $request, Student $student)` - Update
  - [x] `destroy(Student $student)` - Delete
  - [x] `myStudents(Request $request)` - Teacher's class

### 3.4 Student Validation
**MCP**: `search-docs queries: ["form request validation", "image validation"]`

- [x] Run: `php artisan make:request StoreStudentRequest --no-interaction`
  - [x] Validation rules: name, student_id, class, section, photo
  - [x] Photo validation: image, max:2048, mimes:jpg,png
  - [x] Custom error messages
- [x] Run: `php artisan make:request UpdateStudentRequest --no-interaction`
  - [x] Same as store, but photo optional

### 3.5 Student Resource
**MCP**: `search-docs queries: ["api resources", "resource collections"]`

- [x] Run: `php artisan make:resource StudentResource --no-interaction`
- [x] Define transformed data:
  - [x] Include all student fields
  - [x] Add `photo_url` accessor
  - [x] Include created_by user (when loaded)

### 3.6 Student Routes
- [x] Add to `routes/api.php`:
  - [x] Resource routes: `Route::apiResource('students', StudentController::class)`
  - [x] Custom route: `GET /api/my-students`
  - [x] Apply `auth:sanctum` middleware

### 3.7 Testing - Student Module
- [ ] Run: `php artisan make:test Student/StudentTest --pest --no-interaction`
- [ ] Write tests:
  - [ ] `it('allows admin to view all students')`
  - [ ] `it('allows teacher to view only their class students')`
  - [ ] `it('prevents teacher from viewing other class students')`
  - [ ] `it('allows admin to create student with photo')`
  - [ ] `it('prevents teacher from creating student')`
  - [ ] `it('uploads and optimizes photo correctly')`
  - [ ] `it('generates unique slug for student')`
- [ ] Run: `php artisan test --filter=StudentTest`

---

## Phase 4: Attendance System (3-4 hours)

### 4.1 Attendance Policy
**MCP**: `search-docs queries: ["policies authorization"]`

- [ ] Run: `php artisan make:policy AttendancePolicy --model=Attendance --no-interaction`
- [ ] Implement methods:
  - [ ] `create(User $user)` - Both admin and teacher
  - [ ] `viewReport(User $user, string $class)` - Admin: any, Teacher: own class

### 4.2 Attendance Service
**MCP**: `mcp_sequentialthinking` for bulk recording algorithm

- [x] Create `app/Services/Attendance/AttendanceService.php`:
  - [x] `recordBulkAttendance(User $user, array $records, string $date): array`
  - [x] `getMonthlyReport(User $user, int $month, ?string $class): array`
  - [x] `getDashboardSummary(User $user): array`
  - [ ] `calculateAttendancePercentage(Student $student, int $month): float`

### 4.3 Attendance Controller
- [x] Run: `php artisan make:controller AttendanceController --no-interaction`
- [x] Inject `AttendanceService` in constructor
- [x] Implement methods:
  - [x] `bulkStore(BulkAttendanceRequest $request)` - Record bulk
  - [x] `monthlyReport(Request $request)` - Generate report
  - [x] `dashboardSummary(Request $request)` - Dashboard stats

### 4.4 Attendance Validation
- [x] Run: `php artisan make:request BulkAttendanceRequest --no-interaction`
  - [x] Validation rules:
    - [x] `date` required, date format
    - [x] `records` required, array
    - [x] `records.*.student_id` required, exists
    - [x] `records.*.status` required, in enum values
    - [x] `records.*.note` nullable, string

### 4.5 Attendance Resource
- [x] Run: `php artisan make:resource AttendanceResource --no-interaction`
- [x] Define transformed data:
  - [x] Include all fields
  - [x] Include student relationship
  - [x] Include recorder user

### 4.6 Attendance Routes
- [x] Add to `routes/api.php`:
  - [x] `POST /api/attendance/bulk`
  - [x] `GET /api/reports/monthly`
  - [x] `GET /api/dashboard/summary`
  - [x] Apply `auth:sanctum` middleware

### 4.7 Caching Strategy
**MCP**: `search-docs queries: ["redis caching", "cache tags"]`

 - [ ] Update `AttendanceService` for caching:
  - [x] Cache dashboard summary (configurable TTL)
  - [ ] Cache monthly reports (1 hour)
  - [x] User-specific cache keys
  - [x] Implement cache invalidation on new attendance

### 4.8 Testing - Attendance Module
- [ ] Run: `php artisan make:test Attendance/AttendanceTest --pest --no-interaction`
- [ ] Write tests:
  - [x] `it('records bulk attendance in transaction')`
  - [x] `it('prevents teacher from recording other class attendance')`
  - [ ] `it('prevents duplicate attendance for same date')`
  - [x] `it('generates monthly report with correct data')`
  - [ ] `it('filters report by class for teachers')`
  - [ ] `it('caches dashboard summary correctly')`
  - [ ] `it('invalidates cache on new attendance')`
- [ ] Run: `php artisan test --filter=AttendanceTest`

---

## Phase 5: Advanced Features (2-3 hours)

### 5.1 Artisan Command
**MCP**: `search-docs queries: ["artisan commands", "command arguments"]`

- [ ] Run: `php artisan make:command GenerateAttendanceReport --no-interaction`
- [ ] Configure command:
  - [ ] Signature: `attendance:generate-report {month} {class}`
  - [ ] Description: Generate monthly attendance report CSV
  - [ ] Use `AttendanceService` for data
  - [ ] Export to CSV format
  - [ ] Store in `storage/reports/`

### 5.2 Events & Listeners
**MCP**: `search-docs queries: ["events listeners", "queued listeners"]`

- [ ] Run: `php artisan make:event BulkAttendanceRecorded --no-interaction`
  - [ ] Add properties: `$user`, `$records`, `$date`
- [ ] Run: `php artisan make:listener SendAttendanceNotification --event=BulkAttendanceRecorded --no-interaction`
  - [ ] Implement `ShouldQueue` interface
  - [ ] Add notification logic (can be simple log for now)
- [ ] Update `AttendanceService::recordBulkAttendance()`:
  - [ ] Dispatch `BulkAttendanceRecorded` event after success

### 5.3 Dashboard Enhancements
- [ ] Update `AttendanceService::getDashboardSummary()`:
  - [ ] Calculate today's stats (present, absent, late)
  - [ ] Add weekly trend data
  - [ ] Format for Chart.js compatibility
  - [ ] Apply role-based filtering

### 5.4 Testing - Advanced Features
- [ ] Run: `php artisan make:test Commands/GenerateReportTest --pest --no-interaction`
  - [ ] Test command execution
  - [ ] Verify CSV file created
- [ ] Run: `php artisan make:test Events/AttendanceEventTest --pest --no-interaction`
  - [ ] Test event dispatched
  - [ ] Test listener queued

---

## Phase 6: Testing & Quality (2 hours)

### 6.1 Code Formatting
- [ ] Run: `vendor/bin/pint --dirty`
- [ ] Fix any formatting issues
- [ ] Re-run: `vendor/bin/pint --test`

### 6.2 Run All Tests
- [ ] Run: `php artisan test`
- [ ] Verify all tests passing
- [ ] Check test coverage

### 6.3 Manual Testing
- [ ] Test login flow
- [ ] Test token refresh
- [ ] Test photo upload
- [ ] Test bulk attendance
- [ ] Test dashboard with both admin and teacher
- [ ] Test monthly report generation

### 6.4 Documentation
- [x] Create `.env.example` with all required variables
- [ ] Update `README.md`:
  - [ ] Setup instructions
  - [ ] API endpoints list
  - [ ] Authentication flow
  - [ ] Testing commands
- [x] Create `AI_WORKFLOW.md`:
  - [x] Which parts used AI
  - [x] 3 specific helpful prompts
  - [x] Development speed improvements
  - [x] Manual vs AI-generated breakdown

### 6.5 Git Cleanup
- [ ] Review all commits
- [ ] Ensure semantic commit messages
- [ ] Verify `.gitignore` is complete
- [ ] Push to repository

---

## Final Verification

### Functional Requirements
- [ ] ✅ Authentication with Sanctum (access + refresh tokens)
- [ ] ✅ RBAC (admin vs teacher roles)
- [ ] ✅ Student CRUD with photo upload
- [ ] ✅ Bulk attendance recording
- [ ] ✅ Monthly report generation
- [ ] ✅ Dashboard with statistics
- [ ] ✅ Artisan command for reports

### Technical Requirements
- [ ] ✅ Service layer pattern
- [ ] ✅ Policy-based authorization
- [ ] ✅ FormRequest validation
- [ ] ✅ API Resources for responses
- [ ] ✅ Redis caching
- [ ] ✅ N+1 query prevention
- [ ] ✅ Audit trail (Loggable trait)
- [ ] ✅ Photo optimization (UUID flat structure)

### Code Quality
- [ ] ✅ All files have `declare(strict_types=1)`
- [ ] ✅ All methods have type hints
- [ ] ✅ All public methods have PHPDoc
- [ ] ✅ PSR-12 compliant (Pint)
- [ ] ✅ All tests passing

### Performance
- [ ] ✅ Dashboard loads < 500ms
- [ ] ✅ Bulk attendance < 1s for 30 students
- [ ] ✅ No N+1 queries detected
- [ ] ✅ Photos optimized to ~200KB

---

**Status**: Ready for implementation ✅  
**Estimated Time**: 12-16 hours  
**Next Step**: Begin Phase 1.1 (Project Setup)
