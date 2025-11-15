Build a Mini School Attendance System with AI-assisted development workflow documentation.
Requirements

Part 1: Laravel Backend (60%)

Create a REST API with these features:
1. Student Management
• Model: Student (name, student_id, class, section, photo)
• CRUD endpoints with validation
• Use Laravel Resource for API responses
2. Attendance Module
• Model: Attendance (student_id, date, status, note, recorded_by)
• Bulk attendance recording endpoint
• Query optimization for attendance reports
• Generate monthly attendance report (eager loading required)
3. Advanced Features
• Implement a Service Layer for attendance business logic
• Add a custom Artisan command: attendance:generate-report {month} {class}
• Use Laravel Events/Listeners for attendance notifications
• Implement Redis caching for attendance statistics

Part 2: Vue.js Frontend (30%)

Create a simple SPA:

1. Student List Page
• Display students with search/filter by class
• Pagination (use Laravel pagination)
2. Attendance Recording Interface
• Select class/section, load students
• Mark attendance (Present/Absent/Late) with bulk action
• Show real-time attendance percentage
3. Dashboard
• Display today’s attendance summary
• Monthly attendance chart (use Chart.js or similar)


Part 3: AI Development Documentation (10%)

Create a AI_WORKFLOW.md file documenting:

1. Which parts you used AI assistance for (Claude Code/Cursor/ChatGPT)
2. 3 specific prompts you used and how they helped
3. How AI improved your development speed
4. What you manually coded vs AI-generated

Technical Requirements
• Laravel 10+, Vue 3 (Composition API preferred)
• Database: MySQL/PostgreSQL with proper migrations
• Authentication: Laravel Sanctum (simple token-based)
• Follow SOLID principles
• Write at least 3 unit tests for critical features
• Docker setup (optional but impressive)
• Clean Git history with meaningful commits

Evaluation Criteria
• Code quality and architecture (30%)
• Feature completeness (25%)
• AI tool utilization effectiveness (15%)
• Vue.js implementation (15%)
• Testing & documentation (15%)

Submission Instructions

Required Files
1. GitHub Repository (public or give access)
README.md with:
• Setup instructions
• Database setup commands
• How to run the project
• Test credentials (if applicable)

2. AI Workflow Documentation (as specified per test level)

3. .env.example file with all required variables
GitHub Repository Should Include
• Clean commit history (not one giant commit)
• Proper .gitignore
• All dependencies listed in composer.json/package.json
• Database migrations and seeders

Deadline
Tomorrow (November 16, 2025) at 11:59 PM Bangladesh Time

Submit To

Reply to the email with:
• Subject: “[Your Name] - Full-Stack Engineer Technical Test Submission”
• GitHub repository link
• Any special notes or instructions
