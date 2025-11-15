## ⚠️ IMPORTANT NOTICE

**This document is now superseded by comprehensive documentation suite.**

**👉 START HERE**: [START_HERE.md](START_HERE.md) - Your complete development guide

---

## 📚 Documentation Index

**Essential Reading** (in order):

1. **[START_HERE.md](START_HERE.md)** ⭐ - Complete development guide
2. **[CODING_CONVENTIONS.md](CODING_CONVENTIONS.md)** ⭐ - Your exact lara-api-starter patterns
3. **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** ⚡ - Print and keep beside you
4. **[IMPLEMENTATION_CHECKLIST.md](IMPLEMENTATION_CHECKLIST.md)** - 121-step tracker

**Reference Documentation**:

5. **[ARCHITECTURE.md](ARCHITECTURE.md)** - System design decisions
6. **[DEVELOPMENT_RULES.md](DEVELOPMENT_RULES.md)** - Strict coding standards
7. **[MCP_SERVERS_GUIDE.md](MCP_SERVERS_GUIDE.md)** - Mandatory MCP usage

---

## Legacy Content Below (Original Plan)

## Plan: LMST School Attendance API with Sanctum & Simple RBAC

Laravel 12 REST API for mini school attendance system using **Laravel Sanctum with stateful access/refresh tokens** and **simple role-based access control** (admin vs teacher).

### Architecture Decisions
✅ **Authentication**: Sanctum with stateful tokens (database storage)  
✅ **RBAC**: Simple user_type check (admin/teacher) - no permission enums  
✅ **Photo Upload**: Flat UUID structure (`storage/students/{uuid}.jpg`) with immediate optimization  
✅ **Service Layer**: Business logic separated from controllers  
✅ **Code Organization**: Inspired by lara-api-starter (Services, Traits, Lib, BaseController)

### Steps

1. **Setup Foundation & Authentication** - Configure Sanctum for stateful tokens with refresh rotation, create `SanctumTokenService` with unified response format `{success, data, message, code}`, set up directory structure (`app/Services/`, `app/Traits/`, `app/Lib/`), create `BaseController` with `sendResponse()`/`sendError()` methods, implement `JsonResponse` utility for global exception handling, and configure `config/sanctum.php` for token expiry (access: 60min, refresh: 30 days).

2. **Database Schema & Models** - Create migrations for [`users`](table) (add user_type enum [admin/teacher], class/section nullable for teachers, audit fields: created_by/updated_by/created_ip/updated_ip), [`students`](table) (name, student_id unique, class, section, photo, slug, audit fields), [`attendances`](table) (student_id, date, status enum [present/absent/late], note, recorded_by), create models with relationships (User hasMany Students, Student hasMany Attendances, Attendance belongsTo Student/User), implement [`Loggable`](trait) trait for automatic audit logging, add [`ImageOptimizable`](trait) ✅ trait for photo handling with UUID flat structure, and create factories + seeders (admin user, teacher users with class assignments, students with photos).

3. **Student Management Module** - Create `StudentPolicy` with simple checks (admin: allow all, teacher: check class match), build `StudentService` with methods (`createStudent()`, `updateStudent()`, `getStudentsByClass()` with eager loading `with('attendances')`), implement `StudentController` extending `BaseController` (index/store/show/update/destroy with policy authorization), create validation (`StoreStudentRequest`/`UpdateStudentRequest` with image validation rules), build `StudentResource` for consistent API responses, add teacher-specific `GET /api/my-students` endpoint (auto-filters by auth user's class/section), and handle photo upload using `ImageOptimizable` trait (500x500, 85% quality, UUID filename).

4. **Attendance System with Service Layer** - Create `Attendance` model with relationships (`belongsTo Student`, `belongsTo User as recorder`), implement `AttendancePolicy` (admin: any class, teacher: own class validation), build `AttendanceService` with transaction-wrapped `recordBulkAttendance()` method (validates class access, uses `updateOrCreate()`, dispatches event), create `POST /api/attendance/bulk` endpoint (accepts array of student_id + status + note), implement `GET /api/reports/monthly` with role-based logic (admin accepts class param, teacher uses own class), add Redis caching for dashboard stats with user-specific keys (`dashboard:{user_type}:{user_id}`), and prevent N+1 queries using `with()` eager loading throughout.

5. **Advanced Features & Commands** - Create Artisan command `attendance:generate-report {month} {class}` (generates CSV using service layer data), implement event `BulkAttendanceRecorded` with queued listener for notifications (`ShouldQueue` interface), build dashboard `GET /api/dashboard/summary` with conditional logic (admin: school-wide stats, teacher: class-only stats), add Chart.js compatible response format for monthly attendance trends, ensure all routes use `auth:sanctum` middleware, and add simple role checks in policies (`$user->user_type === 'admin'` or class matching for teachers).

6. **Testing, Documentation & Quality** - Write Pest feature tests (RBAC: "teacher cannot view other class", "admin can manage any student", Auth: "login returns tokens", "refresh rotates token"), create unit tests for `AttendanceService` and `StudentService` methods, run `vendor/bin/pint --dirty` for PSR-12 compliance, ensure comprehensive PHPDoc blocks with `@context` and `@pattern` tags, create `AI_WORKFLOW.md` documenting AI assistance usage, update `README.md` with setup instructions and API endpoints, create `.env.example` with Sanctum and Redis config, and verify photo storage is symlinked (`php artisan storage:link`).

### Implementation Notes

**Simplified RBAC** - No permission enums needed! Just check `user_type`:
```php
// In policies
if ($user->user_type === 'admin') return true;
if ($user->user_type === 'teacher' && $student->class === $user->class) return true;
```

**Photo Upload Flow** ✅:
```php
// Using ImageOptimizable trait
$filename = $this->uploadAndOptimizeImage($request->file('photo'), 'students');
$student->photo = $filename; // Stores: "550e8400-e29b-41d4-a716-446655440000.jpg"
$student->save();

// Public URL: /storage/students/{uuid}.jpg
```

**Sanctum Token Flow**:
```php
// Login: Returns access_token + refresh_token
// Refresh: Rotates both tokens (revokes old, issues new)
// Logout: Revokes current token
```
