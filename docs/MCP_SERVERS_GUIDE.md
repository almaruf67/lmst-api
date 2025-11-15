# MCP Servers Usage Guide

**Project**: LMST API  
**Purpose**: Mandatory guide for using MCP servers during development  
**Last Updated**: November 15, 2025

---

## Overview

This project REQUIRES the use of three MCP servers throughout development:
1. **Laravel Boost** - Primary tool for Laravel-specific features
2. **Context7** - Third-party package documentation
3. **Sequential Thinking** - Complex problem solving

**Rule**: Never implement a feature without consulting the appropriate MCP server first.

---

## 1. Laravel Boost - PRIMARY TOOL

### When to Use
**MANDATORY before implementing ANY Laravel feature:**
- Authentication (Sanctum, sessions, tokens)
- Authorization (policies, gates)
- Validation (FormRequests, rules)
- Eloquent (relationships, scopes, eager loading)
- Routing (middleware, resource routes)
- Testing (Pest, factories, seeders)
- Queues (jobs, events, listeners)
- Caching (Redis, tags, invalidation)

### Available Tools

#### 1. `search-docs` (Most Important)
**Use this FIRST before any implementation:**

```php
// Pattern: search-docs queries: ["topic 1", "topic 2", "topic 3"]

// Example 1: Before implementing authentication
search-docs queries: [
    "sanctum authentication",
    "personal access tokens",
    "token refresh",
    "stateful tokens"
]

// Example 2: Before creating policies
search-docs queries: [
    "policies authorization",
    "resource policies",
    "policy methods"
]

// Example 3: Before validation
search-docs queries: [
    "form requests validation",
    "custom validation rules",
    "validation error messages"
]

// Example 4: Before relationships
search-docs queries: [
    "eloquent relationships",
    "belongsTo hasMany",
    "eager loading",
    "n+1 query prevention"
]

// Example 5: Before testing
search-docs queries: [
    "pest testing",
    "feature tests api",
    "database testing"
]
```

**Search Strategy**:
1. Use multiple broad queries (3-5)
2. Keep queries simple (2-4 words)
3. Don't include package names in queries
4. Use topic-based searches, not implementation questions

#### 2. `tinker` Tool
**Use for debugging and testing Eloquent queries:**

```php
// Check if student exists
tinker: Student::where('student_id', 'STD001')->first()

// Test relationship loading
tinker: Student::with('attendances')->find(1)

// Verify user authentication
tinker: User::where('email', 'admin@test.com')->first()

// Test policy manually
tinker: Gate::allows('update', Student::find(1))
```

#### 3. `database-query` Tool
**Use for read-only database inspection:**

```sql
-- Check students table structure
database-query: DESCRIBE students

-- Verify attendance records
database-query: SELECT * FROM attendances WHERE date = CURDATE() LIMIT 10

-- Check user roles
database-query: SELECT id, email, user_type FROM users WHERE user_type = 'admin'
```

#### 4. `list-artisan-commands` Tool
**Use before running artisan commands to verify options:**

```bash
# Before creating model
list-artisan-commands: make:model

# Before creating controller
list-artisan-commands: make:controller

# Before creating test
list-artisan-commands: make:test
```

### Laravel Boost Workflow

**For EVERY feature implementation:**

```
Step 1: Search docs
├─ search-docs queries: ["feature topic 1", "feature topic 2"]
├─ Read returned documentation
└─ Understand Laravel's recommended approach

Step 2: Implement based on docs
├─ Follow Laravel conventions from docs
├─ Use proper method signatures
└─ Apply best practices shown in docs

Step 3: Test with tinker (if needed)
├─ tinker: Test Eloquent queries
├─ tinker: Verify relationships
└─ tinker: Check data integrity

Step 4: Verify with database-query (if needed)
└─ database-query: Inspect actual database state
```

---

## 2. Context7 - PACKAGE DOCUMENTATION

### When to Use
**Use for ANY non-Laravel package:**
- Intervention Image (image manipulation)
- Spatie Sluggable (slug generation)
- Laravel Excel (if using exports)
- DomPDF (if generating PDFs)
- Any Composer package not made by Laravel

### Two-Step Process

#### Step 1: Resolve Library ID
```bash
# Intervention Image
mcp_context7_resolve-library-id "intervention/image"
# Returns: /intervention/image

# Spatie Sluggable
mcp_context7_resolve-library-id "spatie/laravel-sluggable"
# Returns: /spatie/laravel-sluggable
```

#### Step 2: Get Documentation
```bash
# Get image manipulation docs
mcp_context7_get-library-docs library: "/intervention/image" 
                              topic: "resize optimize encode"

# Get slug generation docs
mcp_context7_get-library-docs library: "/spatie/laravel-sluggable"
                              topic: "automatic slug generation"
```

### Context7 Workflow

**When working with third-party packages:**

```
Step 1: Identify package name
└─ Check composer.json or documentation

Step 2: Resolve library ID
└─ mcp_context7_resolve-library-id "vendor/package"

Step 3: Get specific documentation
├─ mcp_context7_get-library-docs with library ID
├─ Specify relevant topic
└─ Read returned docs carefully

Step 4: Implement following docs
├─ Use correct API methods
├─ Follow package conventions
└─ Apply configuration if needed
```

### Common Packages for This Project

#### Intervention Image (Photo Upload)
```bash
# Resolve
mcp_context7_resolve-library-id "intervention/image"

# Get docs for common tasks
mcp_context7_get-library-docs "/intervention/image" "resize crop"
mcp_context7_get-library-docs "/intervention/image" "encode jpeg quality"
mcp_context7_get-library-docs "/intervention/image" "save storage"
```

#### Spatie Sluggable (Student Slugs)
```bash
# Resolve
mcp_context7_resolve-library-id "spatie/laravel-sluggable"

# Get docs
mcp_context7_get-library-docs "/spatie/laravel-sluggable" "slug configuration"
mcp_context7_get-library-docs "/spatie/laravel-sluggable" "unique slugs"
```

---

## 3. Sequential Thinking - COMPLEX PROBLEMS

### When to Use
**Use for problems requiring multi-step logical reasoning:**
- Architectural decisions (> 3 components)
- Complex algorithms (bulk operations, optimizations)
- Performance optimization strategies
- Cache invalidation logic
- Query optimization plans
- Debugging complex issues with multiple causes

### How to Use

```bash
# Provide clear, detailed problem description
mcp_sequentialthinking "Design an efficient bulk attendance recording system that:
- Accepts 30-50 student records at once
- Validates each student belongs to teacher's class
- Uses transactions for data integrity
- Prevents duplicate records for same date
- Dispatches events after successful recording
- Invalidates relevant caches
- Completes within 1 second"
```

### Use Cases for This Project

#### 1. Bulk Attendance Recording Algorithm
```bash
mcp_sequentialthinking "Design bulk attendance recording algorithm:

Requirements:
- Accept array of [{student_id, status, note}]
- Validate teacher can only record for their class
- Prevent duplicates (student + date unique)
- Use database transactions
- Dispatch event after success
- Invalidate dashboard cache
- Handle partial failures

Constraints:
- Must complete in < 1 second for 30 students
- Must be atomic (all or nothing)
- Must log errors for failed records

Output: Step-by-step implementation plan with code structure"
```

#### 2. Monthly Report Optimization
```bash
mcp_sequentialthinking "Optimize monthly attendance report generation:

Current Issue:
- Reports take 3-5 seconds for 500 students
- N+1 query problems
- No caching implemented

Requirements:
- Load all students with attendance for given month
- Calculate attendance percentages
- Filter by class/section (role-based)
- Cache results for 1 hour
- Invalidate cache when new attendance recorded

Output: Query optimization strategy with eager loading plan"
```

#### 3. Cache Invalidation Strategy
```bash
mcp_sequentialthinking "Design comprehensive cache invalidation strategy:

Cached Data:
- Dashboard stats (per user)
- Monthly reports (per class/month)
- Student lists (per class/section)

Invalidation Triggers:
- New attendance recorded → invalidate dashboard + report
- Student created/updated → invalidate student list
- Student deleted → invalidate all related caches

Considerations:
- Redis tag-based invalidation
- User-specific vs global caches
- Performance impact of mass invalidation

Output: Complete cache key structure and invalidation flow"
```

#### 4. Photo Storage Migration
```bash
mcp_sequentialthinking "Plan migration from local to S3 storage:

Current: storage/app/public/students/{uuid}.jpg
Target: S3 bucket with CloudFront CDN

Requirements:
- Zero downtime during migration
- Maintain photo URLs for existing records
- Update ImageOptimizable trait for S3
- Handle both local and S3 during transition

Constraints:
- 500+ existing photos
- Keep URLs backward compatible
- Must work in local dev environment

Output: Migration plan with rollback strategy"
```

#### 5. Database Query Optimization
```bash
mcp_sequentialthinking "Debug slow query performance:

Problem: /api/students endpoint taking 2-3 seconds

Current Query:
- Student::with('attendances')->paginate(20)

Environment:
- 1000 students in database
- 30,000 attendance records
- MySQL 8.0

Symptoms:
- Fast on first 2 pages
- Slows down significantly on page 10+
- High CPU usage during query

Output: Root cause analysis and optimization steps"
```

### Sequential Thinking Workflow

```
Step 1: Clearly define the problem
├─ State current situation
├─ Describe desired outcome
├─ List all constraints
└─ Mention relevant context

Step 2: Invoke Sequential Thinking
└─ mcp_sequentialthinking with detailed prompt

Step 3: Review generated solution
├─ Check each reasoning step
├─ Verify logic is sound
└─ Identify potential issues

Step 4: Implement proposed solution
├─ Follow step-by-step plan
├─ Test each component
└─ Validate final result

Step 5: Refine if needed
└─ Use Sequential Thinking again for edge cases
```

---

## Workflow Integration

### Feature Implementation Flow

```
┌─────────────────────────────────┐
│  New Feature Requirement        │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│  Is it Laravel-specific?        │
├─────────────┬───────────────────┤
│ YES         │ NO                │
│             │                   │
│ ┌───────────▼─────────────┐    │
│ │ Laravel Boost            │    │
│ │ search-docs first        │    │
│ └───────────┬─────────────┘    │
│             │                   │
│             ▼                   │
│ ┌───────────────────────────┐  │
│ │ Read docs & implement     │  │
│ └───────────┬───────────────┘  │
│             │                   │
├─────────────┤                   │
              │                   │
              ▼                   │
    ┌─────────────────────────┐  │
    │ Third-party package?    │  │
    ├─────────┬───────────────┘  │
    │ YES     │ NO                │
    │         │                   │
    │ ┌───────▼─────────────┐    │
    │ │ Context7            │    │
    │ │ get-library-docs    │    │
    │ └───────┬─────────────┘    │
    │         │                   │
    │         ▼                   │
    │ ┌─────────────────────┐    │
    │ │ Read docs & impl    │    │
    │ └───────┬─────────────┘    │
    │         │                   │
    └─────────┤                   │
              │                   │
              ▼                   │
    ┌─────────────────────────┐  │
    │ Is it complex (>3 steps)?  │
    ├─────────┬───────────────┘  │
    │ YES     │ NO                │
    │         │                   │
    │ ┌───────▼─────────────┐    │
    │ │ Sequential Thinking  │    │
    │ │ for architecture     │    │
    │ └───────┬─────────────┘    │
    │         │                   │
    │         ▼                   │
    │ ┌─────────────────────┐    │
    │ │ Follow plan         │    │
    │ └───────┬─────────────┘    │
    │         │                   │
    └─────────┤                   │
              │                   │
              ▼                   │
    ┌─────────────────────────┐  │
    │ Implement feature        │  │
    └─────────┬───────────────┘  │
              │                   │
              ▼                   │
    ┌─────────────────────────┐  │
    │ Test with tinker/tests   │  │
    └─────────┬───────────────┘  │
              │                   │
              ▼                   │
    ┌─────────────────────────┐  │
    │ Verify with DB query     │  │
    └─────────┬───────────────┘  │
              │                   │
              ▼                   │
    ┌─────────────────────────┐  │
    │ Feature Complete         │  │
    └─────────────────────────┘  │
```

---

## Quick Reference

### Most Common Searches

#### Authentication
```bash
search-docs queries: ["sanctum authentication", "personal access tokens", "token refresh"]
```

#### Authorization
```bash
search-docs queries: ["policies authorization", "gate authorization"]
```

#### Validation
```bash
search-docs queries: ["form request validation", "validation rules"]
```

#### Relationships
```bash
search-docs queries: ["eloquent relationships", "eager loading", "n+1 prevention"]
```

#### Testing
```bash
search-docs queries: ["pest testing", "feature tests", "database testing"]
```

#### Image Processing
```bash
mcp_context7_resolve-library-id "intervention/image"
mcp_context7_get-library-docs "/intervention/image" "resize encode"
```

#### Complex Algorithm
```bash
mcp_sequentialthinking "Design [algorithm description with requirements and constraints]"
```

---

## Troubleshooting

### Issue: search-docs returns no results
**Solution**: Simplify your query. Use broader terms.
```bash
# ❌ Too specific
search-docs queries: ["how to implement sanctum refresh token rotation"]

# ✅ Better
search-docs queries: ["sanctum tokens", "token refresh"]
```

### Issue: Context7 library not found
**Solution**: Check exact package name in composer.json
```bash
# Verify package name first
cat composer.json | grep intervention

# Then resolve with exact name
mcp_context7_resolve-library-id "intervention/image"
```

### Issue: Sequential Thinking gives generic answer
**Solution**: Provide more specific context and constraints
```bash
# ❌ Too vague
mcp_sequentialthinking "Optimize attendance queries"

# ✅ Better
mcp_sequentialthinking "Optimize attendance monthly report query:
- Current: 3s for 500 students
- Target: <1s
- Using: MySQL 8.0
- Issue: N+1 on attendances relationship
Provide step-by-step optimization with eager loading strategy"
```

---

## Remember

1. **Laravel Boost**: First stop for ANY Laravel feature
2. **Context7**: Required for third-party packages
3. **Sequential Thinking**: Use for complex multi-step problems
4. **Never skip**: These tools save hours of debugging
5. **Read carefully**: Documentation returned is version-specific and accurate

**Efficiency Note**: Spending 5 minutes with MCP servers saves 30-60 minutes of trial-and-error implementation.
