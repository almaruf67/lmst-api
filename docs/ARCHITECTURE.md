# LMST API Architecture

**Project**: Mini School Attendance System  
**Framework**: Laravel 12  
**Authentication**: Laravel Sanctum (Stateful Tokens)  
**Last Updated**: November 15, 2025

---

## Core Architecture Decisions

### 1. Authentication Strategy
**Decision**: Laravel Sanctum with stateful personal access tokens  
**Rationale**: 
- Database storage for immediate token revocation
- Built-in refresh token rotation
- Simpler than OAuth2 for this use case
- Native Laravel 12 support

**Token Configuration**:
```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SANCTUM_ACCESS_TOKEN_EXPIRY=60        # 60 minutes
SANCTUM_REFRESH_TOKEN_EXPIRY=43200    # 30 days
```

**Flow**:
1. Login → Issues access + refresh token
2. API calls → Uses access token (Bearer)
3. Token expired → Use refresh token to rotate both
4. Logout → Revokes current token

---

### 2. RBAC Strategy (Simplified)
**Decision**: Simple `user_type` enum check - NO permission enums  
**Rationale**:
- Requirements only need admin vs teacher roles
- Over-engineering with permission system adds unnecessary complexity
- Faster development time
- Easier to maintain

**User Types**:
```php
enum UserType: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
}
```

**Authorization Pattern**:
```php
// In Policies
public function view(User $user, Student $student): bool
{
    if ($user->user_type === 'admin') {
        return true; // Admin: full access
    }
    
    if ($user->user_type === 'teacher') {
        // Teacher: only their class
        return $student->class === $user->class 
            && $student->section === $user->section;
    }
    
    return false;
}
```

---

### 3. Photo Upload Strategy
**Decision**: UUID-based flat directory structure  
**Rationale**:
- **Scalability**: No nested directory limits
- **CDN-Ready**: Works seamlessly with S3/DigitalOcean Spaces
- **Security**: No student ID exposure in URLs
- **Simple Cleanup**: Easy to identify orphaned files

**Storage Structure**:
```
storage/app/public/students/
├── 550e8400-e29b-41d4-a716-446655440000.jpg
├── 6ba7b810-9dad-11d1-80b4-00c04fd430c8.jpg
└── ...
```

**Optimization**:
- Resize: 500x500 (square crop)
- Quality: 85% JPEG
- Max size: ~200KB
- Format: Always JPEG (consistent)

---

### 4. Service Layer Pattern
**Decision**: Business logic in dedicated service classes  
**Rationale**:
- Controllers stay thin (routing + validation only)
- Testable business logic
- Reusable across controllers/commands
- Transaction management centralized

**Directory Structure**:
```
app/Services/
├── Auth/
│   ├── SanctumTokenService.php    # Token issuance/refresh
│   └── AuthService.php            # Login/logout logic
├── Student/
│   └── StudentService.php         # CRUD, filtering, photos
└── Attendance/
    └── AttendanceService.php      # Bulk recording, reports
        # Also exposes getTodayDashboardSummary() with caching
```

---

### 5. Response Architecture
**Decision**: 3-layer response system  
**Rationale**:
- Consistent API responses across all endpoints
- Global exception handling
- Frontend can predict response structure

**Layers**:
1. **Bootstrap** (`bootstrap/app.php`): Catches all exceptions globally
2. **JsonResponse** (`app/Lib/JsonResponse.php`): Formats responses
3. **BaseController** (`app/Http/Controllers/Controller.php`): Provides helper methods

**Response Format**:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "code": 200
}
```

**Error Format**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { "email": ["The email field is required."] },
  "code": 422
}
```

---

## Database Schema

### Users Table
```php
- id: bigint
- name: string
- email: string (unique)
- password: string
- user_type: enum('admin', 'teacher')
- class: string (nullable, for teachers)
- section: string (nullable, for teachers)
- created_by: bigint (nullable)
- updated_by: bigint (nullable)
- created_ip: string (nullable)
- updated_ip: string (nullable)
- created_at, updated_at, deleted_at
```

### Students Table
```php
- id: bigint
- name: string
- student_id: string (unique)
- class: string
- section: string
- photo: string (nullable, UUID filename)
- slug: string (unique, auto-generated)
- created_by, updated_by: bigint (nullable)
- created_ip, updated_ip: string (nullable)
- created_at, updated_at, deleted_at
```

### Attendances Table
```php
- id: bigint
- student_id: bigint (foreign)
- date: date
- status: enum('present', 'absent', 'late')
- note: text (nullable)
- recorded_by: bigint (foreign to users)
- created_at, updated_at

- unique(student_id, date) # One record per student per day
- index(date, status) # For fast reporting
```

---

## API Endpoint Structure

### Authentication
```
POST   /api/login            # Login with email/password
POST   /api/refresh          # Rotate tokens
POST   /api/logout           # Revoke token
GET    /api/me               # Get authenticated user
```

### Students
```
GET    /api/students              # List (admin: all, teacher: filtered)
POST   /api/students              # Create (admin only)
GET    /api/students/{id}         # View single

### Dashboard
GET    /api/dashboard/summary     # Today’s summary (role-aware, cached)

Caching Keys:
- admin: `dashboard:admin:YYYY-MM-DD`
- teacher: `dashboard:teacher:{user_id}:YYYY-MM-DD`

TTL:
- `config('cache.dashboard_ttl', 60)` seconds (set via `DASHBOARD_CACHE_TTL`)
PUT    /api/students/{id}         # Update (admin only)
DELETE /api/students/{id}         # Delete (admin only)
GET    /api/my-students           # Teacher's class (auto-filtered)
```

### Attendance
```
POST   /api/attendance/bulk       # Bulk record attendance
GET    /api/reports/monthly       # Monthly report (role-based)
GET    /api/dashboard/summary     # Today's stats (role-based)
```

---

## Middleware Stack

### Route Groups
```php
// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (all students/attendance)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('students', StudentController::class);
    // ... other routes
});
```

### Automatic Middleware (via bootstrap/app.php)
- `TrustProxies` - Trust load balancers
- `ValidatePostSize` - Prevent large payloads
- `TrimStrings` - Clean input
- `ConvertEmptyStringsToNull` - Normalize empty values

---

## Caching Strategy

### Redis Usage
```php
// Dashboard stats (user-specific)
Cache::remember("dashboard:{$user->user_type}:{$user->id}", 300, function() {
    // Calculate stats
});

// Monthly reports (class-specific)
Cache::remember("report:{$month}:{$class}:{$section}", 3600, function() {
    // Generate report
});
```

### Cache Invalidation
```php
// On attendance record
Cache::forget("dashboard:{$user->user_type}:{$user->id}");
Cache::forget("report:{$month}:{$class}:{$section}");
```

---

## Security Measures

### 1. Authentication
- Sanctum personal access tokens (database-stored)
- Token expiry enforcement
- Refresh token rotation on use

### 2. Authorization
- Policy-based checks on every resource action
- User type validation in policies
- Class/section matching for teachers

### 3. Input Validation
- FormRequest classes for all mutations
- File upload validation (images only, max 2MB)
- SQL injection prevention via Eloquent ORM

### 4. Audit Trail
- `Loggable` trait tracks:
  - `created_by` / `updated_by` (user ID)
  - `created_ip` / `updated_ip` (request IP)
  - Soft deletes for data recovery

---

## Performance Optimizations

### 1. N+1 Query Prevention
```php
// Always eager load relationships
Student::with('attendances')->get();
Attendance::with(['student', 'recorder'])->get();
```

### 2. Database Indexing
```php
// Critical indexes
$table->index(['class', 'section']); // Filter by class
$table->index(['date', 'status']);   // Attendance reports
$table->unique(['student_id', 'date']); // Prevent duplicates
```

### 3. Redis Caching
- Dashboard stats: 5 minutes
- Monthly reports: 1 hour
- User session data: 60 minutes

### 4. Lazy Loading
- Student photos loaded on demand
- Pagination for large datasets (20 per page)

---

## Testing Strategy

### Feature Tests (Priority)
```php
// Authentication
- Login returns valid tokens
- Refresh rotates both tokens
- Logout revokes token

// RBAC
- Admin can view all students
- Teacher can only view own class
- Teacher cannot delete students

// Photo Upload
- Valid image uploads successfully
- Invalid file types rejected
- Old photo deleted on update
```

### Unit Tests
```php
// Services
- AttendanceService::recordBulkAttendance()
- StudentService::getStudentsByClass()
- ImageOptimizable trait methods
```

---

## Error Handling

### Global Exception Handler (bootstrap/app.php)
```php
- ModelNotFoundException → 404 JSON response
- AuthenticationException → 401 JSON response
- AuthorizationException → 403 JSON response
- ValidationException → 422 JSON response (with errors)
- Generic Exception → 500 JSON response (log details)
```

### Custom Exceptions
```php
// app/Exceptions/
- AttendanceException (duplicate, invalid date)
- PhotoUploadException (size, format)
```

---

## Development Workflow

### 1. Always Check Laravel Boost Docs
```bash
# Before implementing authentication
Use: search-docs tool with query: "sanctum token authentication"

# Before creating policies
Use: search-docs tool with query: "policies authorization"
```

### 2. Use Context7 for Packages
```bash
# When working with Intervention Image
Use: mcp_context7_get-library-docs for "/intervention/image"

# When working with Spatie Sluggable
Use: mcp_context7_get-library-docs for "/spatie/laravel-sluggable"
```

### 3. Sequential Thinking for Complex Logic
```bash
# Use for:
- Designing bulk attendance algorithm
- Optimizing monthly report queries
- Planning cache invalidation strategy
```

---

## File Naming Conventions

### Models
```
Student.php, Attendance.php, User.php
```

### Controllers
```
StudentController.php, AttendanceController.php, AuthController.php
```

### Services
```
StudentService.php, AttendanceService.php, SanctumTokenService.php
```

### Requests
```
StoreStudentRequest.php, UpdateStudentRequest.php, BulkAttendanceRequest.php
```

### Resources
```
StudentResource.php, AttendanceResource.php, UserResource.php
```

### Policies
```
StudentPolicy.php, AttendancePolicy.php
```

### Traits
```
Loggable.php, ImageOptimizable.php
```

---

## Configuration Files

### Required Environment Variables
```env
# App
APP_NAME="LMST API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lmst_api
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SANCTUM_ACCESS_TOKEN_EXPIRY=60
SANCTUM_REFRESH_TOKEN_EXPIRY=43200

# Storage
FILESYSTEM_DISK=public
```

---

## Deployment Checklist

- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed database: `php artisan db:seed --force`
- [ ] Link storage: `php artisan storage:link`
- [ ] Cache config: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Optimize: `php artisan optimize`
- [ ] Queue workers: `php artisan queue:work --daemon`

---

**Note**: This architecture is designed for the specific requirements of a school attendance system with simple RBAC. Do not over-engineer beyond these specifications without explicit approval.
