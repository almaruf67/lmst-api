# LMST API Development Workflow

**Project**: Mini School Attendance System  
**Framework**: Laravel 12 + Sanctum  
**Deadline**: November 16, 2025 (11:59 PM BDT)

---

## Quick Reference

### Architecture Decisions ✅
- **Auth**: Sanctum stateful tokens (DB storage for revocation)
- **RBAC**: Simple user_type check (admin/teacher) - NO permission enums
- **Photos**: UUID flat structure + immediate optimization ✅
- **Service Layer**: Business logic separated from controllers
- **Response Format**: Unified `{success, data, message, code}`

### Key Files Implemented
- ✅ `app/Traits/ImageOptimizable.php` - Photo upload with UUID + optimization

---

## Development Phases

### Phase 1: Foundation (2-3 hours)
**Goal**: Setup authentication and base architecture

**Tasks**:
1. Configure Sanctum for stateful tokens
2. Create `SanctumTokenService` (password + refresh grant)
3. Build `BaseController` with `sendResponse()`/`sendError()`
4. Implement `JsonResponse` for global exception handling
5. Create `Loggable` trait for audit fields
6. Setup directory structure (`app/Services/`, `app/Lib/`)

**Verification**:
```bash
php artisan test --filter=AuthTest
curl -X POST /api/login -d '{"email":"admin@test.com","password":"password"}'
```

---

### Phase 2: Student Module (3-4 hours)
**Goal**: Complete student management with photo upload

**Tasks**:
1. Create migrations (students table with slug, photo, audit fields)
2. Build `Student` model with `ImageOptimizable` trait ✅
3. Implement `StudentPolicy` (simple role checks)
4. Create `StudentService` with CRUD methods
5. Build `StudentController` + validation requests
6. Create `StudentResource` for API responses
7. Add `/api/my-students` for teachers

**Verification**:
```bash
php artisan test --filter=StudentTest
# Upload photo and verify UUID filename in storage/app/public/students/
```

---

### Phase 3: Attendance System (3-4 hours)
**Goal**: Bulk attendance recording with role-based access

**Tasks**:
1. Create `attendances` migration
2. Build `Attendance` model with relationships
3. Implement `AttendancePolicy` (class matching for teachers)
4. Create `AttendanceService` (bulk recording + monthly reports)
5. Build endpoints (`/api/attendance/bulk`, `/api/reports/monthly`)
6. Add Redis caching for dashboard stats
7. Implement eager loading to prevent N+1

**Verification**:
```bash
php artisan test --filter=AttendanceTest
# Test bulk recording for 30 students < 1 second
```

---

### Phase 4: Advanced Features (2-3 hours)
**Goal**: Commands, events, dashboard

**Tasks**:
1. Create `attendance:generate-report` Artisan command
2. Implement `BulkAttendanceRecorded` event + listener
3. Build dashboard `/api/dashboard/summary` with role logic
4. Add Chart.js compatible response format
5. Setup Redis caching with user-specific keys

**Verification**:
```bash
php artisan attendance:generate-report 11 "Class 1"
php artisan test --filter=DashboardTest
```

---

### Phase 5: Testing & Documentation (2 hours)
**Goal**: Comprehensive tests and documentation

**Tasks**:
1. Write Pest feature tests (RBAC, Auth, CRUD)
2. Create unit tests for services
3. Run `vendor/bin/pint --dirty`
4. Document AI workflow in `AI_WORKFLOW.md`
5. Update `README.md` with setup + API docs
6. Create `.env.example`
7. Verify `php artisan storage:link`

**Verification**:
```bash
php artisan test
vendor/bin/pint --test
```

---

## Simple RBAC Implementation

**No Permission Enums Needed!**

```php
// In StudentPolicy.php
public function view(User $user, Student $student): bool
{
    // Admin can view any student
    if ($user->user_type === 'admin') {
        return true;
    }
    
    // Teacher can only view their class students
    if ($user->user_type === 'teacher') {
        return $student->class === $user->class 
            && $student->section === $user->section;
    }
    
    return false;
}

public function create(User $user): bool
{
    // Only admin can create students
    return $user->user_type === 'admin';
}
```

**Frontend (Nuxt.js)**:
```vue
<template>
  <!-- Simple user_type checks -->
  <button v-if="authStore.isAdmin">Delete Student</button>
  <div v-else>Your Class: {{ authStore.user.class }}</div>
</template>
```

---

## Photo Upload Flow ✅

**Implementation** (using `ImageOptimizable` trait):

```php
// In StudentController@store
$student = new Student();

if ($request->hasFile('photo')) {
    $filename = $student->uploadAndOptimizeImage(
        $request->file('photo'),
        'students',  // Directory
        500,         // Max width
        500,         // Max height
        85           // Quality %
    );
    $student->photo = $filename;
}

$student->save();

// Stored as: storage/app/public/students/{uuid}.jpg
// URL: /storage/students/{uuid}.jpg
```

**Update with new photo**:
```php
// In StudentController@update
if ($request->hasFile('photo')) {
    $filename = $student->replaceImage(
        $request->file('photo'),
        $student->photo,  // Old filename (will be deleted)
        'students'
    );
    $student->photo = $filename;
}
```

---

## API Endpoints Structure

### Authentication
- `POST /api/login` - Login (returns access + refresh tokens)
- `POST /api/refresh` - Refresh token rotation
- `POST /api/logout` - Revoke current token
- `GET /api/me` - Get authenticated user

### Students (Admin + Teacher with class filter)
- `GET /api/students` - List all (admin) or filtered by class (teacher)
- `POST /api/students` - Create (admin only)
- `GET /api/students/{id}` - View single
- `PUT /api/students/{id}` - Update (admin only)
- `DELETE /api/students/{id}` - Delete (admin only)
- `GET /api/my-students` - Teacher's class students (auto-filtered)

### Attendance
- `POST /api/attendance/bulk` - Record bulk attendance
- `GET /api/reports/monthly?month=11&class=1` - Monthly report

### Dashboard
- `GET /api/dashboard/summary` - Today's stats (role-based)

---

## Testing Strategy

### Feature Tests
```php
// tests/Feature/StudentTest.php
it('allows admin to view all students')
it('allows teacher to view only their class students')
it('prevents teacher from viewing other class students')
it('uploads and optimizes photo correctly')

// tests/Feature/AttendanceTest.php
it('records bulk attendance in transaction')
it('prevents teacher from recording other class attendance')
it('generates monthly report with correct data')
```

### Unit Tests
```php
// tests/Unit/AttendanceServiceTest.php
it('calculates attendance percentage correctly')
it('filters students by class and date range')
```

---

## Time Estimate Breakdown

| Phase | Duration | Priority |
|-------|----------|----------|
| Foundation & Auth | 2-3h | High |
| Student Module | 3-4h | High |
| Attendance System | 3-4h | High |
| Advanced Features | 2-3h | Medium |
| Testing & Docs | 2h | High |
| **Total** | **12-16h** | - |

---

## AI Assistance Documentation

**Document in `AI_WORKFLOW.md`**:
1. Which parts used AI (migrations, controllers, policies)
2. 3 specific prompts and their impact
3. Speed improvement estimation
4. Manual vs AI-generated code breakdown

**Example Prompts**:
- "Create StudentPolicy with simple admin/teacher class checks"
- "Generate bulk attendance service method with transaction"
- "Build ImageOptimizable trait with UUID flat structure"

---

## Success Criteria

✅ All API endpoints working with proper authentication  
✅ RBAC enforced (admin full access, teacher class-limited)  
✅ Photo upload with UUID optimization functional  
✅ Bulk attendance recording < 1 second for 30 students  
✅ All Pest tests passing  
✅ Code follows PSR-12 (Pint validation)  
✅ Comprehensive documentation (README + AI_WORKFLOW)  
✅ Clean Git history with semantic commits
