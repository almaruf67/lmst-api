# LMST API - Mini School Attendance System

**Laravel 12** | **PHP 8.3** | **Sanctum v4** | **Pest v4**

A production-ready Laravel API for school attendance management with role-based access control (admin/teacher).

---

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate
php artisan db:seed

# Link storage
php artisan storage:link

# Run tests
php artisan test
```

---

## 📚 Documentation

**REQUIRED READING** (in this order):

1. **[CODING_CONVENTIONS.md](docs/CODING_CONVENTIONS.md)** ⭐ - Your exact coding patterns from lara-api-starter
   - BaseController pattern (sendResponse/sendError)
   - Service layer structure with transactions
   - PHPDoc standards with @context and @pattern
   - Testing patterns (Arrange-Act-Assert)

2. **[QUICK_REFERENCE.md](docs/QUICK_REFERENCE.md)** ⚡ - Print and keep beside you
   - Copy-paste code templates
   - MCP server cheatsheet
   - Pre-commit checklist
   - Common mistakes

3. **[ARCHITECTURE.md](docs/ARCHITECTURE.md)** - System design decisions
   - Database schema
   - API endpoints
   - Security measures
   - Caching strategy

4. **[DEVELOPMENT_RULES.md](docs/DEVELOPMENT_RULES.md)** - Strict coding standards
   - PHP 8.3 requirements
   - Laravel 12 conventions
   - Testing requirements
   - Git commit format

5. **[MCP_SERVERS_GUIDE.md](docs/MCP_SERVERS_GUIDE.md)** - Mandatory MCP usage
   - Laravel Boost search-docs examples
   - Context7 for package documentation
   - Sequential Thinking for complex problems

6. **[IMPLEMENTATION_CHECKLIST.md](docs/IMPLEMENTATION_CHECKLIST.md)** - 121-step tracker
   - Phase-by-phase breakdown
   - MCP usage at each step
   - Final verification checklist

---

## 🎯 Key Features

- ✅ **Sanctum Authentication**: Stateful personal access tokens with refresh rotation
- ✅ **Simple RBAC**: Admin and teacher roles (no complex permission enums)
- ✅ **Student Management**: CRUD with photo upload (UUID flat structure)
- ✅ **Bulk Attendance**: Record attendance for entire class in one request
- ✅ **Monthly Reports**: Generate attendance statistics with caching
- ✅ **Dashboard**: Role-specific summaries with Chart.js data
- ✅ **Audit Trails**: Automatic tracking with Loggable trait
- ✅ **Photo Optimization**: 500x500, JPEG 85%, ~200KB target

---

## 🏗️ Architecture

### Response Format (3-Layer)
```json
{
    "message": "Student created successfully",
    "data": { ... },
    "version": "v1"
}
```

### Authentication Flow
```
POST /api/login → {access_token, refresh_token, user}
GET /api/me (auth:sanctum) → {user with role}
POST /api/refresh → {new tokens}
POST /api/logout → revoke tokens
```

### RBAC Pattern
```php
// Simple user_type checks (no permission enums)
if ($user->user_type === 'admin') {
    return true; // Allow all
}

if ($user->user_type === 'teacher') {
    return $student->class === $user->class; // Class match
}
```

---

## 📁 Project Structure

```
app/
├── Http/Controllers/API/
│   ├── BaseController.php          # sendResponse/sendError methods
│   ├── Auth/AuthController.php
│   ├── StudentController.php
│   └── AttendanceController.php
├── Services/
│   ├── Auth/SanctumTokenService.php
│   ├── Student/StudentService.php
│   └── Attendance/AttendanceService.php
├── Models/
│   ├── User.php                    # user_type enum, class/section
│   ├── Student.php                 # photo, slug, Loggable trait
│   └── Attendance.php              # status enum, date
├── Traits/
│   ├── Loggable.php                # Auto audit trails
│   └── ImageOptimizable.php        # UUID photo uploads
├── Lib/
│   └── JsonResponse.php            # Global response formatter
└── Enums/
    ├── UserType.php                # Admin | Teacher
    └── AttendanceStatus.php        # Present | Absent | Late
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthenticationTest.php

# Run with filter
php artisan test --filter=StudentTest

# Check code style
vendor/bin/pint --dirty
```

**Testing Pattern**:
```php
it('allows admin to create student', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    // Act
    $response = $this->postJson('/api/students', [...]);

    // Assert
    $response->assertSuccessful();
    $this->assertDatabaseHas('students', [...]);
});
```

---

## 🔒 Security

- **Token Expiry**: Access 60min, Refresh 30 days
- **Policy-Based Auth**: Resource policies for all CRUD operations
- **Audit Logging**: Automatic tracking with Loggable trait (created_by, updated_by, IPs)
- **Input Validation**: FormRequest classes with custom messages
- **XSS Protection**: Middleware enabled globally
- **Rate Limiting**: Apply at middleware level for sensitive endpoints

---

## 🚀 API Endpoints

### Authentication
```
POST   /api/login        # Authenticate and get tokens
POST   /api/refresh      # Refresh access token
POST   /api/logout       # Revoke tokens
GET    /api/me           # Get authenticated user
```

### Students (auth:sanctum)
```
GET    /api/students           # List all (admin: all, teacher: own class)
POST   /api/students           # Create (admin only)
GET    /api/students/{id}      # View single
PUT    /api/students/{id}      # Update (admin only)
DELETE /api/students/{id}      # Delete (admin only)
GET    /api/my-students        # Teacher's class students
```

### Attendance (auth:sanctum)
```
POST   /api/attendance/bulk           # Record bulk attendance
GET    /api/reports/monthly           # Monthly report (filtered by role)
GET    /api/dashboard/summary         # Dashboard stats
```

---

## ⚙️ Configuration

### Environment Variables
```env
# Sanctum Token Expiry
SANCTUM_ACCESS_TOKEN_EXPIRY=60          # minutes
SANCTUM_REFRESH_TOKEN_EXPIRY=43200      # minutes (30 days)

# Redis Cache
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Image Storage
FILESYSTEM_DISK=public
```

### Cache Keys Pattern
```php
// User-specific caching
Cache::remember("dashboard:{user_type}:{user_id}", 300, function() { ... });
```

---

## 🎓 Development Workflow

### Before Starting ANY Feature:

1. **Read CODING_CONVENTIONS.md** - Understand BaseController, service layer, PHPDoc patterns
2. **Use MCP Servers**:
   ```bash
   @mcp_laravel-boost_search-docs queries: ["feature-name"]
   ```
3. **Reference lara-api-starter** - Check similar patterns in boilerplate
4. **Follow IMPLEMENTATION_CHECKLIST.md** - 121-step guide

### Implementation Pattern:

1. Search docs with MCP → 2. Create files → 3. Implement following conventions → 4. Write tests → 5. Run Pint → 6. Commit

---

## 🤖 MCP Servers (Mandatory Usage)

### Laravel Boost
```bash
# BEFORE implementing ANY feature
@mcp_laravel-boost_search-docs queries: ["authentication", "policies"]
```

### Context7
```bash
# For package-specific docs
@mcp_context7_resolve-library-id intervention/image
@mcp_context7_get-library-docs /intervention/image "resize"
```

### Sequential Thinking
```bash
# For complex problems
@mcp_sequentialthinking "Design bulk attendance algorithm"
```

**See [MCP_SERVERS_GUIDE.md](docs/MCP_SERVERS_GUIDE.md) for detailed examples.**

---

## 📋 Pre-Commit Checklist

- [ ] Used `search-docs` before implementing?
- [ ] Extends `BaseController`?
- [ ] Using `sendResponse()` / `sendError()`?
- [ ] Service layer for business logic?
- [ ] DB transactions for multi-step ops?
- [ ] Type hints on all parameters?
- [ ] PHPDoc with `@context` and `@pattern`?
- [ ] Eager loading to prevent N+1?
- [ ] Error logging with context?
- [ ] Test written and passing?
- [ ] Run: `vendor/bin/pint --dirty`

---

## 🐛 Troubleshooting

**Issue**: "Vite manifest not found"  
**Solution**: Run `npm run build` or `npm run dev`

**Issue**: "Class not found"  
**Solution**: Run `composer dump-autoload`

**Issue**: Token not working  
**Solution**: Check Sanctum middleware in `bootstrap/app.php`

**Issue**: Photo upload fails  
**Solution**: Run `php artisan storage:link`

---

## 📦 Dependencies

- **Laravel 12**: Latest framework features
- **Sanctum v4**: Stateful authentication
- **Pest v4**: Testing framework with browser tests
- **Intervention Image v3**: Photo optimization
- **Spatie Sluggable**: Automatic slug generation
- **Redis**: Caching layer

---

## 👥 Team

**Developer**: Your Name  
**Project Type**: School attendance management system  
**Timeline**: November 16, 2025 deadline  
**Estimated Work**: 12-16 hours

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 🙏 Credits

- Built following **lara-api-starter** boilerplate patterns
- Powered by **Laravel 12** and **Sanctum v4**
- Tested with **Pest v4**
- Documentation enhanced with **MCP servers** (Laravel Boost, Context7, Sequential Thinking)

---

**Remember**: MCP servers FIRST, then code. Follow CODING_CONVENTIONS.md exactly. No exceptions! 🚀


In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
