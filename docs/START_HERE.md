# 🎯 START HERE - LMST API Development Guide

**Last Updated**: November 15, 2025  
**Deadline**: November 16, 2025 (11:59 PM BDT)  
**Status**: Documentation complete, ready for implementation

---

## 📖 What You Have

You now have a **complete development documentation suite** that follows your exact coding conventions from **lara-api-starter**. Everything is ready for Phase 1 implementation.

---

## 🚀 Quick Start (< 5 minutes)

### Step 1: Read Documentation (in this order)

**⭐ CRITICAL - Read these 3 files first:**

1. **[CODING_CONVENTIONS.md](CODING_CONVENTIONS.md)** (10 min read)
   - Your exact BaseController pattern (`sendResponse()` / `sendError()`)
   - Service layer structure with DB transactions
   - PHPDoc standards with `@context` and `@pattern` tags
   - FormRequest validation patterns
   - Loggable trait for audit trails
   - Testing patterns (Arrange-Act-Assert)
   - Copy-paste templates for Controller, Service, FormRequest

2. **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** (5 min read)
   - Print this and keep beside you while coding!
   - Copy-paste code templates ready to use
   - MCP server cheatsheet
   - Pre-commit checklist
   - Common mistakes to avoid

3. **[IMPLEMENTATION_CHECKLIST.md](IMPLEMENTATION_CHECKLIST.md)** (scan it)
   - 121-step phase-by-phase breakdown
   - Start with Phase 1.1 (Project Setup)
   - Check off items as you complete them

**📚 Reference docs (read when needed):**

4. **[ARCHITECTURE.md](ARCHITECTURE.md)** - System design decisions
5. **[DEVELOPMENT_RULES.md](DEVELOPMENT_RULES.md)** - Strict coding standards
6. **[MCP_SERVERS_GUIDE.md](MCP_SERVERS_GUIDE.md)** - Mandatory MCP usage examples

---

## 🎯 Your Coding Convention Summary

### From lara-api-starter boilerplate:

**✅ BaseController Pattern** (MANDATORY):
```php
class StudentController extends BaseController  // Not Controller!
{
    public function store(Request $request): JsonResponse
    {
        return $this->sendResponse($student, 'Created', 201);
        // NEVER return response()->json([...])
    }
}
```

**✅ Service Layer Pattern** (MANDATORY):
```php
class StudentService
{
    public function createStudent(array $data): Student
    {
        DB::beginTransaction();
        try {
            $student = Student::create($data);
            DB::commit();
            Log::info('Student created', ['id' => $student->id]);
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

**✅ Response Format** (MANDATORY):
```json
{
    "message": "Student created successfully",
    "data": { ... },
    "version": "v1"
}
```

**✅ PHPDoc Standards** (MANDATORY):
```php
/**
 * Create a new student
 *
 * @param array<string, mixed> $data Student data
 * @return Student Created student instance
 * @throws \Exception
 * @context Use this for creating students with photo upload
 * @pattern Service layer with transaction, logging, and error handling
 */
public function createStudent(array $data): Student
```

**✅ Constructor Property Promotion** (MANDATORY):
```php
public function __construct(
    private readonly StudentService $studentService
) {}
```

---

## 🤖 MCP Server Usage (MANDATORY)

**BEFORE implementing ANY feature**, you MUST:

```bash
# Step 1: Search Laravel/Sanctum docs
@mcp_laravel-boost_search-docs queries: ["feature-name", "related-topic"]

# Step 2: If package-specific (e.g., Intervention Image)
@mcp_context7_resolve-library-id intervention/image
@mcp_context7_get-library-docs /intervention/image "resize images"

# Step 3: For complex algorithms
@mcp_sequentialthinking "Design bulk attendance recording algorithm"
```

**Common Searches**:
- Authentication: `["sanctum authentication", "personal access tokens"]`
- Policies: `["policies", "authorization", "resource policies"]`
- Validation: `["form request validation"]`
- Relationships: `["eloquent relationships", "eager loading"]`

**See [MCP_SERVERS_GUIDE.md](MCP_SERVERS_GUIDE.md) for 10+ detailed examples.**

---

## 📁 Files You Already Have

### ✅ Created Files

1. **app/Traits/ImageOptimizable.php** - Photo upload with UUID flat structure
   - `uploadAndOptimizeImage()` - Resize to 500x500, JPEG 85%
   - `replaceImage()` - Delete old, upload new
   - `deleteImage()` - Remove from storage
   - `getImageUrl()` - Generate public URL

2. **docs/plan.md** - Technical feature specification
3. **docs/workflow.md** - 5-phase development plan
4. **docs/ARCHITECTURE.md** - Complete system design
5. **docs/DEVELOPMENT_RULES.md** - Strict coding standards
6. **docs/MCP_SERVERS_GUIDE.md** - MCP server usage instructions
7. **docs/IMPLEMENTATION_CHECKLIST.md** - 121-step tracker
8. **docs/CODING_CONVENTIONS.md** - Your exact lara-api-starter patterns ⭐
9. **docs/QUICK_REFERENCE.md** - Quick reference card ⚡
10. **README.md** - Updated project documentation

### 📦 Reference from lara-api-starter

Located at: `/var/www/laravel/laravel-modular/lara-api-starter/`

Key files to reference:
- `app/Http/Controllers/API/BaseController.php` - Your BaseController pattern
- `app/Lib/JsonResponse.php` - Global response formatter
- `app/Traits/Loggable.php` - Audit trail pattern
- `app/Services/Admin/UserService.php` - Service layer example
- `app/Services/PassportTokenService.php` - Token service pattern
- `app/Http/Controllers/API/Admin/AuthController.php` - Auth controller example

---

## 🚦 Implementation Workflow

### Phase 1: Foundation & Authentication (2-3 hours)

**Current State**: Documentation complete, ready to code

**Next Steps**:

1. **Project Setup** (10 min):
   ```bash
   mkdir -p app/Services/{Auth,Student,Attendance}
   mkdir -p app/Lib app/Enums
   php artisan storage:link
   ```

2. **Before Coding BaseController** (5 min):
   ```bash
   @mcp_laravel-boost_search-docs queries: ["api responses", "json responses"]
   ```

3. **Create BaseController** (15 min):
   - Copy pattern from lara-api-starter
   - Location: `app/Http/Controllers/API/BaseController.php`
   - Methods: `sendResponse()`, `sendError()`, `sendWarning()`

4. **Create JsonResponse** (20 min):
   - Copy from lara-api-starter: `app/Lib/JsonResponse.php`
   - Update `bootstrap/app.php` for global exception handling

5. **Create Loggable Trait** (10 min):
   - Copy from lara-api-starter: `app/Traits/Loggable.php`

6. **Before Coding Auth Service** (5 min):
   ```bash
   @mcp_laravel-boost_search-docs queries: ["sanctum authentication", "personal access tokens", "token refresh"]
   ```

7. **Create SanctumTokenService** (30 min):
   - Location: `app/Services/Auth/SanctumTokenService.php`
   - Follow PassportTokenService pattern with unified response format
   - Methods: `issueToken()`, `refreshToken()`, `revokeToken()`

8. **Create AuthController** (20 min):
   - Run: `php artisan make:controller Auth/AuthController --no-interaction`
   - Extend `BaseController`
   - Inject `SanctumTokenService`
   - Methods: `login()`, `refresh()`, `logout()`, `me()`

9. **Create FormRequests** (15 min):
   - `php artisan make:request Auth/LoginRequest --no-interaction`
   - `php artisan make:request Auth/RefreshRequest --no-interaction`
   - Follow lara-api-starter FormRequest pattern

10. **Add Routes** (10 min):
    - Update `routes/api.php`

11. **Write Tests** (30 min):
    - `php artisan make:test Auth/AuthenticationTest --pest --no-interaction`
    - Test login, refresh, logout, me endpoints

12. **Run Tests** (5 min):
    ```bash
    php artisan test --filter=AuthenticationTest
    vendor/bin/pint --dirty
    ```

**After Phase 1**: You'll have working authentication with Sanctum tokens following your exact conventions.

---

## ✅ Pre-Coding Checklist

**Before writing ANY code:**

- [ ] Read CODING_CONVENTIONS.md
- [ ] Read QUICK_REFERENCE.md
- [ ] Print QUICK_REFERENCE.md and keep beside you
- [ ] Have lara-api-starter open in another VS Code window
- [ ] MCP servers ready (`@mcp_laravel-boost_search-docs`)
- [ ] Open IMPLEMENTATION_CHECKLIST.md to track progress

---

## 🎯 Critical Success Factors

### 1. **ALWAYS Use MCP Servers First**
```bash
# Pattern: Search → Read → Implement
@mcp_laravel-boost_search-docs queries: ["feature"]
# Read results, understand pattern
# Then implement following CODING_CONVENTIONS.md
```

### 2. **ALWAYS Follow lara-api-starter Patterns**
- Extend `BaseController` (not `Controller`)
- Use `sendResponse()` / `sendError()` methods
- Service layer with DB transactions
- Constructor property promotion
- Type hints with array shapes
- PHPDoc with `@context` and `@pattern`

### 3. **ALWAYS Test Before Committing**
```bash
vendor/bin/pint --dirty              # Format code
php artisan test --filter=Feature    # Run tests
```

---

## 🚨 Common Mistakes to Avoid

❌ **DON'T**:
1. Skip MCP server searches before coding
2. Return raw arrays: `response()->json([...])`
3. Skip type hints: `function create($data)`
4. Skip DB transactions for multi-step operations
5. Forget eager loading: causes N+1 queries
6. Skip error logging: `Log::error()`
7. Skip PHPDoc tags: `@context` and `@pattern`

✅ **DO**:
1. Search docs first: `@mcp_laravel-boost_search-docs`
2. Use BaseController: `$this->sendResponse()`
3. Type hint everything: `function create(array $data): Model`
4. Wrap in transactions: `DB::transaction(function() { ... })`
5. Eager load: `->with(['relation'])`
6. Log errors: `Log::error('Context', ['data' => $data])`
7. Full PHPDoc: `@context`, `@pattern`, `@param`, `@return`

---

## 📊 Progress Tracking

### Completed ✅
- Requirements analysis
- Architecture decisions (Sanctum stateful, simplified RBAC, UUID photos)
- ImageOptimizable trait implementation
- Complete documentation suite (10 files)
- Coding conventions from lara-api-starter
- MCP server usage guide
- 121-step implementation checklist

### Next Steps 🚀
- [ ] Phase 1: Foundation & Authentication (2-3 hours)
- [ ] Phase 2: Database Schema & Models (2-3 hours)
- [ ] Phase 3: Student Module (3-4 hours)
- [ ] Phase 4: Attendance System (3-4 hours)
- [ ] Phase 5: Advanced Features (2-3 hours)
- [ ] Phase 6: Testing & Quality (2 hours)

**Estimated Total**: 12-16 hours  
**Deadline**: November 16, 2025 (11:59 PM BDT)  
**Time Remaining**: ~24 hours

---

## 🆘 Need Help?

### Stuck on something?

**Follow this order**:

1. **Check CODING_CONVENTIONS.md** - Find your exact pattern
2. **Search docs**: `@mcp_laravel-boost_search-docs queries: ["topic"]`
3. **Reference lara-api-starter** - Check similar file
4. **Use Sequential Thinking**: `@mcp_sequentialthinking "Problem description"`

### Quick Links

- **Conventions**: [CODING_CONVENTIONS.md](CODING_CONVENTIONS.md)
- **Quick Ref**: [QUICK_REFERENCE.md](QUICK_REFERENCE.md)
- **Checklist**: [IMPLEMENTATION_CHECKLIST.md](IMPLEMENTATION_CHECKLIST.md)
- **Architecture**: [ARCHITECTURE.md](ARCHITECTURE.md)
- **MCP Guide**: [MCP_SERVERS_GUIDE.md](MCP_SERVERS_GUIDE.md)

---

## 🎓 Final Reminders

1. **MCP servers are MANDATORY** - Search before EVERY feature
2. **Follow lara-api-starter patterns EXACTLY** - Your conventions are documented
3. **Use IMPLEMENTATION_CHECKLIST.md** - Check off items as you go
4. **Test frequently** - Don't wait until the end
5. **Keep QUICK_REFERENCE.md open** - Copy-paste templates
6. **No documentation during coding** - Only AI_WORKFLOW.md at the end

---

## 🚀 Ready to Start?

**Your next command should be**:

```bash
# Open 3 VS Code windows:
code /var/www/laravel/lmst-api                          # Your project
code /var/www/laravel/laravel-modular/lara-api-starter  # Reference
code docs/QUICK_REFERENCE.md                            # Quick ref

# Then start Phase 1.1:
mkdir -p app/Services/{Auth,Student,Attendance}
mkdir -p app/Lib app/Enums
php artisan storage:link
```

**Good luck! You've got 24 hours and complete documentation. Let's build this! 🚀**

---

**Last Updated**: November 15, 2025  
**Status**: ✅ Documentation Complete - Ready for Implementation  
**Next Step**: Begin Phase 1.1 (Project Setup) from IMPLEMENTATION_CHECKLIST.md
