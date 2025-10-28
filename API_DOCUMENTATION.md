# 🌐 Arkan ERP - API & Routes Documentation

## Overview
This document provides comprehensive information about all routes, endpoints, and workflows in the Arkan ERP system.

### ⚠️ Important Architecture Note
**Arkan ERP uses Web Routes with AJAX, NOT traditional REST API**

Unlike typical modern web applications that separate frontend and backend APIs, Arkan ERP follows Laravel's traditional monolithic architecture:

- **All routes** are defined in `routes/web.php` (1107 lines)
- **Session-based authentication** via Laravel Sanctum
- **CSRF protection** for all state-changing operations
- **Server-Side Rendering** with Blade templates
- **AJAX support** for dynamic interactions
- **Only 1 API endpoint**: `/api/user` (for authenticated user info)

This architecture is common in Laravel applications and provides better security, SEO, and integration with Laravel's ecosystem (Livewire, Blade, etc.).

---

## 📋 Table of Contents
1. [Route Statistics](#route-statistics)
2. [Authentication Routes](#authentication-routes)
3. [Project Management Routes](#project-management-routes)
4. [Task Management Routes](#task-management-routes)
5. [Employee Management Routes](#employee-management-routes)
6. [CRM Routes](#crm-routes)
7. [Admin Routes](#admin-routes)
8. [API Endpoints](#api-endpoints)
9. [Workflow Diagrams](#workflow-diagrams)

---

## 📊 Route Statistics

### ⚠️ Architecture Note
This system uses **Web Routes with AJAX** instead of traditional REST API architecture. All routes are defined in `routes/web.php` (1107 lines).

### Route Count by Module
| Module | Routes | Methods | Type |
|--------|--------|---------|------|
| Authentication (Jetstream) | 15 | GET, POST, DELETE | Web |
| Projects | 80+ | GET, POST, PUT, DELETE | Web + AJAX |
| Tasks | 60+ | GET, POST, PUT, DELETE | Web + AJAX |
| Employee/HR | 70+ | GET, POST, PUT, DELETE | Web + AJAX |
| CRM | 40+ | GET, POST, PUT, DELETE | Web + AJAX |
| Admin | 100+ | GET, POST, PUT, DELETE | Web + AJAX |
| Gamification | 30+ | GET, POST, PUT, DELETE | Web + AJAX |
| Meetings | 25+ | GET, POST, PUT, DELETE | Web + AJAX |
| Social | 15+ | GET, POST, DELETE | Web + AJAX |

**Total Web Routes**: 500+ endpoints (all in `routes/web.php`)  
**Total API Routes**: 1 endpoint only (`/api/user` in `routes/api.php`)

### Route Distribution
- **Web Routes**: 99.8% of all routes
- **API Routes**: 0.2% (Sanctum user endpoint only)
- **AJAX Support**: All web routes support AJAX responses

---

## 🔐 Authentication Routes

### Registration & Login
```
POST   /register                    → Register new user
POST   /login                       → User login
POST   /logout                      → User logout
GET    /email/verify                → Email verification page
POST   /email/verification-notification → Resend verification
```

### Password Reset
```
GET    /forgot-password             → Password reset form
POST   /forgot-password             → Send reset link
GET    /reset-password/{token}      → Reset password form
POST   /reset-password              → Update password
```

### Two-Factor Authentication
```
GET    /two-factor-challenge        → 2FA challenge page
POST   /two-factor-challenge        → Verify 2FA code
POST   /user/two-factor-authentication → Enable 2FA
DELETE /user/two-factor-authentication → Disable 2FA
GET    /user/two-factor-recovery-codes → Get recovery codes
POST   /user/two-factor-recovery-codes → Regenerate codes
```

### Profile Management
```
GET    /user/profile                → View profile
PUT    /user/profile-information    → Update profile
PUT    /user/password               → Change password
DELETE /user                        → Delete account
GET    /user/profile-photo          → Get profile photo
DELETE /user/profile-photo          → Delete profile photo
```

---

## 🚀 Project Management Routes

### Project CRUD
```
GET    /projects                    → List all projects
GET    /projects/create             → Create project form
POST   /projects                    → Store new project
GET    /projects/{id}               → View project details
GET    /projects/{id}/edit          → Edit project form
PUT    /projects/{id}               → Update project
DELETE /projects/{id}               → Delete project
```

### Project Status & Workflow
```
POST   /projects/{id}/complete      → Mark project as complete
POST   /projects/{id}/cancel        → Cancel project
POST   /projects/{id}/resume        → Resume paused project
POST   /projects/{id}/pause         → Pause project
GET    /projects/{id}/check-deletion → Check if deletable
```

### Project Participants
```
GET    /projects/{id}/participants  → List participants
POST   /projects/{id}/participants  → Add participant
DELETE /projects/{id}/participants/{userId} → Remove participant
PUT    /projects/{id}/participants/{userId} → Update participant role
```

### Project Services
```
GET    /projects/{id}/services      → List project services
POST   /projects/{id}/services      → Add service to project
DELETE /projects/{id}/services/{serviceId} → Remove service
PUT    /projects/{id}/services/{serviceId} → Update service details
```

### Project Attachments
```
GET    /projects/{id}/attachments   → List attachments
POST   /projects/{id}/attachments/presigned-url → Get upload URL
POST   /projects/{id}/attachments/upload → Upload file
POST   /projects/{id}/attachments/{attachmentId}/confirm → Confirm upload
GET    /projects/{id}/attachments/{attachmentId} → View attachment
GET    /projects/{id}/attachments/{attachmentId}/download → Download
DELETE /projects/{id}/attachments/{attachmentId} → Delete attachment
```

### Attachment Sharing
```
GET    /projects/attachments/shares → List shared attachments
POST   /projects/attachments/{id}/share → Share attachment
GET    /attachments/shared/{token}  → View shared attachment
GET    /attachments/shared/{token}/download/{id} → Download shared
POST   /projects/attachments/shares/{id}/cancel → Cancel share
```

### Project Approvals (Deliveries)
```
GET    /deliveries                  → List deliveries
POST   /deliveries/{id}/deliver     → Mark as delivered
POST   /deliveries/{id}/undeliver   → Unmark delivery
POST   /deliveries/{id}/approve-administrative → Admin approval
POST   /deliveries/{id}/approve-technical → Technical approval
POST   /deliveries/{id}/reject-administrative → Admin rejection
POST   /deliveries/{id}/reject-technical → Technical rejection
```

### Project Analytics
```
GET    /projects/{id}/analytics     → Project analytics dashboard
GET    /projects/{id}/analytics/tasks → Task analytics
GET    /projects/{id}/analytics/time → Time analytics
GET    /projects/{id}/analytics/team → Team performance
GET    /projects-analytics          → All projects analytics
```

### Project Dashboard
```
GET    /project-dashboard           → Main dashboard
GET    /project-dashboard/departments → Department view
GET    /project-dashboard/teams     → Team view
GET    /project-dashboard/employees → Employee view
GET    /project-dashboard/revisions → Revisions view
```

---

## ✅ Task Management Routes

### Task CRUD
```
GET    /tasks                       → List all tasks
GET    /tasks/create                → Create task form
POST   /tasks                       → Store new task
GET    /tasks/{id}                  → View task details
GET    /tasks/{id}/edit             → Edit task form
PUT    /tasks/{id}                  → Update task
DELETE /tasks/{id}                  → Delete task
```

### Task Assignment
```
POST   /tasks/{id}/assign           → Assign task to user
DELETE /tasks/{id}/users/{userId}   → Remove assignment
POST   /tasks/{id}/reassign         → Reassign to another user
```

### Task Status & Time Tracking
```
POST   /tasks/{id}/start            → Start task
POST   /tasks/{id}/pause            → Pause task
POST   /tasks/{id}/resume           → Resume task
POST   /tasks/{id}/complete         → Complete task
POST   /tasks/{id}/cancel           → Cancel task
GET    /tasks/{id}/time-logs        → Get time logs
```

### Task Revisions
```
GET    /task-revisions              → List all revisions
GET    /task-revisions/{id}         → View revision details
POST   /tasks/{id}/revisions        → Request revision
POST   /task-revisions/{id}/approve → Approve revision
POST   /task-revisions/{id}/reject  → Reject revision
```

### Task Transfers
```
GET    /task-transfers              → List transfer history
POST   /tasks/{id}/transfer         → Transfer task
GET    /task-transfers/{id}         → View transfer details
```

### Task Deliveries
```
GET    /task-deliveries             → List task deliveries
POST   /task-deliveries/{id}/approve-administrative → Admin approval
POST   /task-deliveries/{id}/approve-technical → Technical approval
POST   /task-deliveries/{id}/reject-administrative → Admin rejection
POST   /task-deliveries/{id}/reject-technical → Technical rejection
```

### Template Tasks
```
GET    /template-tasks              → List templates
GET    /template-tasks/create       → Create template form
POST   /template-tasks              → Store template
GET    /template-tasks/{id}         → View template
PUT    /template-tasks/{id}         → Update template
DELETE /template-tasks/{id}         → Delete template
POST   /template-tasks/{id}/assign  → Assign template task
```

### Additional Tasks
```
GET    /additional-tasks            → List additional tasks
GET    /additional-tasks/create     → Create form
POST   /additional-tasks            → Store task
GET    /additional-tasks/{id}       → View task
POST   /additional-tasks/{id}/apply → Apply for task
POST   /additional-tasks/{id}/approve/{userId} → Approve application
POST   /additional-tasks/{id}/reject/{userId}  → Reject application
POST   /additional-tasks/{id}/complete/{userId} → Mark complete
```

### My Tasks
```
GET    /my-tasks                    → My assigned tasks
GET    /my-tasks/active             → Active tasks only
GET    /my-tasks/completed          → Completed tasks
GET    /my-tasks/pending-approval   → Awaiting approval
```

---

## 👥 Employee Management Routes

### Attendance
```
GET    /attendance                  → Attendance dashboard
POST   /attendance/check-in         → Check in
POST   /attendance/check-out        → Check out
GET    /attendance/my-attendance    → My attendance history
GET    /attendance/report           → Attendance report
GET    /attendance/calendar         → Calendar view
POST   /attendance/bulk-assign-shifts → Assign shifts
```

### Absence Requests
```
GET    /absence-requests            → List requests
GET    /absence-requests/create     → Create form
POST   /absence-requests            → Store request
GET    /absence-requests/{id}       → View request
GET    /absence-requests/{id}/edit  → Edit form
PUT    /absence-requests/{id}       → Update request
DELETE /absence-requests/{id}       → Delete request
POST   /absence-requests/{id}/manager-approve → Manager approval
POST   /absence-requests/{id}/manager-reject  → Manager rejection
POST   /absence-requests/{id}/hr-approve     → HR approval
POST   /absence-requests/{id}/hr-reject      → HR rejection
POST   /absence-requests/{id}/reset-status   → Reset status
```

### Permission Requests
```
GET    /permission-requests         → List requests
GET    /permission-requests/create  → Create form
POST   /permission-requests         → Store request
GET    /permission-requests/{id}    → View request
PUT    /permission-requests/{id}    → Update request
DELETE /permission-requests/{id}    → Delete request
POST   /permission-requests/{id}/manager-approve → Manager approval
POST   /permission-requests/{id}/manager-reject  → Manager rejection
POST   /permission-requests/{id}/hr-approve     → HR approval
POST   /permission-requests/{id}/hr-reject      → HR rejection
POST   /permission-requests/{id}/confirm-return → Confirm return
```

### Overtime Requests
```
GET    /overtime-requests           → List requests
GET    /overtime-requests/create    → Create form
POST   /overtime-requests           → Store request
GET    /overtime-requests/{id}      → View request
PUT    /overtime-requests/{id}      → Update request
DELETE /overtime-requests/{id}      → Delete request
POST   /overtime-requests/{id}/manager-approve → Manager approval
POST   /overtime-requests/{id}/manager-reject  → Manager rejection
POST   /overtime-requests/{id}/hr-approve     → HR approval
POST   /overtime-requests/{id}/hr-reject      → HR rejection
```

### Employee Evaluations
```
GET    /employee-evaluations        → List evaluations
GET    /employee-evaluations/create → Create form
POST   /employee-evaluations        → Store evaluation
GET    /employee-evaluations/{id}   → View evaluation
PUT    /employee-evaluations/{id}   → Update evaluation
DELETE /employee-evaluations/{id}   → Delete evaluation
```

### Performance Reviews
```
GET    /reviews                     → List reviews
GET    /reviews/technical/create    → Create technical review
POST   /reviews/technical           → Store technical review
GET    /reviews/marketing/create    → Create marketing review
POST   /reviews/marketing           → Store marketing review
GET    /reviews/coordination/create → Create coordination review
POST   /reviews/coordination        → Store coordination review
GET    /reviews/customer-service/create → Create CS review
POST   /reviews/customer-service    → Store CS review
GET    /my-reviews                  → My reviews
```

### KPI Management
```
GET    /kpi-evaluations             → List KPI evaluations
GET    /kpi-evaluations/create      → Create form
POST   /kpi-evaluations             → Store evaluation
GET    /kpi-evaluations/{id}        → View evaluation
PUT    /kpi-evaluations/{id}        → Update evaluation
DELETE /kpi-evaluations/{id}        → Delete evaluation
```

### Employee Errors
```
GET    /employee-errors             → List errors
GET    /employee-errors/create      → Create form
POST   /employee-errors             → Store error
GET    /employee-errors/{id}        → View error
PUT    /employee-errors/{id}        → Update error
DELETE /employee-errors/{id}        → Delete error
GET    /employee-errors/statistics  → Error statistics
```

### Work Shifts
```
GET    /work-shifts                 → List shifts
GET    /work-shifts/create          → Create form
POST   /work-shifts                 → Store shift
PUT    /work-shifts/{id}            → Update shift
DELETE /work-shifts/{id}            → Delete shift
POST   /assign-shifts               → Assign to employees
```

### Salary Sheets
```
GET    /salary-sheets               → List salary sheets
GET    /salary-sheets/upload        → Upload form
POST   /salary-sheets/upload        → Process upload
GET    /salary-sheets/{id}          → View sheet
POST   /salary-sheets/{id}/send-notifications → Send notifications
```

---

## 📞 CRM Routes

### Clients
```
GET    /clients                     → List clients
GET    /clients/create              → Create form
POST   /clients                     → Store client
GET    /clients/{id}                → View client
GET    /clients/{id}/edit           → Edit form
PUT    /clients/{id}                → Update client
DELETE /clients/{id}                → Delete client
GET    /clients/{id}/projects       → Client projects
GET    /clients/{id}/tickets        → Client tickets
GET    /clients/{id}/call-logs      → Call history
```

### Call Logs
```
GET    /call-logs                   → List call logs
GET    /call-logs/create            → Create form
POST   /call-logs                   → Store log
GET    /call-logs/{id}              → View log
PUT    /call-logs/{id}              → Update log
DELETE /call-logs/{id}              → Delete log
```

### Client Tickets
```
GET    /client-tickets              → List tickets
GET    /client-tickets/create       → Create form
POST   /client-tickets              → Store ticket
GET    /client-tickets/{id}         → View ticket
PUT    /client-tickets/{id}         → Update ticket
DELETE /client-tickets/{id}         → Delete ticket
POST   /client-tickets/{id}/assign  → Assign user
POST   /client-tickets/{id}/resolve → Resolve ticket
POST   /client-tickets/{id}/close   → Close ticket
```

### Ticket Comments
```
GET    /client-tickets/{id}/comments → List comments
POST   /client-tickets/{id}/comments → Add comment
PUT    /comments/{id}               → Update comment
DELETE /comments/{id}                → Delete comment
```

### CRM Dashboard
```
GET    /crm-dashboard               → CRM analytics
GET    /crm-dashboard/tickets       → Ticket statistics
GET    /crm-dashboard/clients       → Client statistics
GET    /crm-dashboard/calls         → Call statistics
```

---

## 🎯 Gamification Routes

### Seasons
```
GET    /seasons                     → List seasons
GET    /seasons/create              → Create form
POST   /seasons                     → Store season
GET    /seasons/{id}                → View season
PUT    /seasons/{id}                → Update season
DELETE /seasons/{id}                → Delete season
POST   /seasons/{id}/activate       → Activate season
GET    /seasons/{id}/leaderboard    → Season leaderboard
GET    /seasons/{id}/statistics     → Season statistics
```

### Badges
```
GET    /badges                      → List badges
GET    /badges/create               → Create form
POST   /badges                      → Store badge
GET    /badges/{id}                 → View badge
PUT    /badges/{id}                 → Update badge
DELETE /badges/{id}                 → Delete badge
```

### Demotion Rules
```
GET    /demotion-rules              → List rules
GET    /demotion-rules/create       → Create form
POST   /demotion-rules              → Store rule
PUT    /demotion-rules/{id}         → Update rule
DELETE /demotion-rules/{id}         → Delete rule
```

### Employee Competition
```
GET    /employee-competition        → Competition dashboard
GET    /employee-competition/leaderboard → Leaderboard
GET    /employee-competition/my-stats → My statistics
```

---

## 🎪 Meeting Management Routes

### Meetings
```
GET    /meetings                    → List meetings
GET    /meetings/create             → Create form
POST   /meetings                    → Store meeting
GET    /meetings/{id}               → View meeting
PUT    /meetings/{id}               → Update meeting
DELETE /meetings/{id}                → Delete meeting
POST   /meetings/{id}/approve       → Approve (client meeting)
POST   /meetings/{id}/reject        → Reject (client meeting)
POST   /meetings/{id}/update-time   → Update time & approve
```

### Meeting Notes
```
GET    /meetings/{id}/notes         → List notes
POST   /meetings/{id}/notes         → Add note
PUT    /meeting-notes/{id}          → Update note
DELETE /meeting-notes/{id}           → Delete note
```

---

## 👑 Admin Routes

### User Management
```
GET    /admin/users                 → List users
GET    /admin/users/create          → Create form
POST   /admin/users                 → Store user
GET    /admin/users/{id}            → View user
PUT    /admin/users/{id}            → Update user
DELETE /admin/users/{id}            → Delete user
POST   /admin/users/{id}/activate   → Activate user
POST   /admin/users/{id}/deactivate → Deactivate user
POST   /admin/users/{id}/suspend    → Suspend user
```

### Role Management
```
GET    /admin/roles                 → List roles
GET    /admin/roles/create          → Create form
POST   /admin/roles                 → Store role
GET    /admin/roles/{id}            → View role
PUT    /admin/roles/{id}            → Update role
DELETE /admin/roles/{id}            → Delete role
POST   /admin/roles/{id}/permissions → Assign permissions
```

### Permission Management
```
GET    /admin/permissions           → List permissions
GET    /admin/permissions/create    → Create form
POST   /admin/permissions           → Store permission
PUT    /admin/permissions/{id}      → Update permission
DELETE /admin/permissions/{id}       → Delete permission
```

### Department Management
```
GET    /admin/departments           → List departments
GET    /admin/departments/create    → Create form
POST   /admin/departments           → Store department
PUT    /admin/departments/{id}      → Update department
DELETE /admin/departments/{id}       → Delete department
```

### Team Management
```
GET    /teams                       → List teams
GET    /teams/create                → Create form
POST   /teams                       → Store team
GET    /teams/{id}                  → View team
PUT    /teams/{id}                  → Update team
DELETE /teams/{id}                  → Delete team
POST   /teams/{id}/members          → Add member
DELETE /teams/{id}/members/{userId} → Remove member
```

### Company Services
```
GET    /company-services            → List services
GET    /company-services/create     → Create form
POST   /company-services            → Store service
PUT    /company-services/{id}       → Update service
DELETE /company-services/{id}       → Delete service
```

### Activity Logs
```
GET    /activity-logs               → View activity log
GET    /activity-logs/user/{id}     → User activity
GET    /activity-logs/model/{type}  → Model activity
```

### System Settings
```
GET    /admin/settings              → System settings
POST   /admin/settings              → Update settings
```

---

## 🌍 API Endpoints

### ⚠️ Important Note
This system primarily uses **Web Routes** with AJAX requests instead of traditional REST API endpoints. All routes are defined in `routes/web.php`.

### Sanctum API Routes (Minimal)

The system has only one Sanctum API route:

```
GET    /api/user                    → Get authenticated user
```

**Usage Example:**
```javascript
fetch('/api/user', {
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
})
```

### AJAX-Based Web Routes

All other operations use web routes with AJAX calls. These routes:
- Use Laravel's CSRF protection
- Support both form submission and AJAX
- Return JSON when requested via AJAX
- Use session-based authentication (auth:sanctum)

**Example - Projects via AJAX:**
```javascript
// List projects (AJAX call to web route)
fetch('/projects', {
  headers: {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': csrfToken
  }
})

// Create project
fetch('/projects', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrfToken
  },
  body: JSON.stringify({...})
})
```

**Example - Tasks via AJAX:**
```javascript
// Start task
fetch('/tasks/1/start', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': csrfToken,
    'Accept': 'application/json'
  }
})
```

**Example - Attendance via AJAX:**
```javascript
// Check in
fetch('/attendance-system/check-in', {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': csrfToken
  }
})
```

### Additional API-Style Routes

Some routes provide JSON responses for specific use cases:

```
GET    /api/user/current-points     → Get current user points
GET    /firebase-config             → Get Firebase configuration
POST   /fcm-token                   → Update FCM token
POST   /fcm-token/delete            → Delete FCM token
```

---

## 🔄 Workflow Diagrams

### Project Creation Workflow
```
1. User accesses /projects/create
2. Fills form (name, client, services, dates)
3. POST /projects
4. System creates project record
5. Generates unique project code
6. Creates Wasabi folder structure
7. Sends notification to operation assistant
8. Redirects to /projects/{id}
```

### Task Assignment & Completion Workflow
```
1. Manager creates task: POST /tasks
2. Assigns to user: POST /tasks/{id}/assign
3. User receives notification
4. User starts task: POST /tasks/{id}/start
5. Time tracking begins
6. User completes: POST /tasks/{id}/complete
7. Approval required:
   a. Administrative approval
   b. Technical approval (if required)
8. Points awarded
9. Task marked as delivered
```

### Absence Request Approval Workflow
```
1. Employee: POST /absence-requests
2. Manager notification sent
3. Manager reviews:
   - Approve: POST /absence-requests/{id}/manager-approve
   - Reject: POST /absence-requests/{id}/manager-reject
4. If manager approves:
   - HR notification sent
   - HR reviews:
     - Approve: POST /absence-requests/{id}/hr-approve
     - Reject: POST /absence-requests/{id}/hr-reject
5. Final status updated
6. Employee notification sent
```

### Project Delivery Approval Workflow
```
1. User delivers work: POST /deliveries/{id}/deliver
2. Administrative approvers notified
3. Admin review:
   - Approve: POST /deliveries/{id}/approve-administrative
   - Reject: POST /deliveries/{id}/reject-administrative
4. If needs technical approval:
   - Technical approvers notified
   - Tech review:
     - Approve: POST /deliveries/{id}/approve-technical
     - Reject: POST /deliveries/{id}/reject-technical
5. If both approved:
   - Project service marked complete
   - Points awarded
   - Notifications sent
```

---

## 🔐 Authentication & Authorization

### Middleware Stack
```
web → auth → verified → role:admin
```

### Common Middleware Combinations
1. **Public Routes**: `web`
2. **Authenticated Routes**: `web, auth`
3. **Verified Routes**: `web, auth, verified`
4. **Admin Routes**: `web, auth, verified, role:admin`
5. **Manager Routes**: `web, auth, verified, role:manager`
6. **HR Routes**: `web, auth, verified, role:hr`

### Permission Checks
Routes use Laravel Policies for authorization:
```php
// Example: Project route
Route::get('/projects/{id}', [ProjectController::class, 'show'])
    ->middleware('can:view,project');
```

---

## 📡 Real-time Features

### Broadcasting Channels
```
Private-App.Models.User.{id}     → User-specific channel
presence-project.{id}             → Project presence
presence-task.{id}                → Task presence
```

### Events Broadcasted
- `TaskAssigned`
- `TaskCompleted`
- `NotificationReceived`
- `ProjectUpdated`
- `AttendanceCheckedIn`

---

## 🚦 Rate Limiting

### API Rate Limits
```
api → 60 requests per minute
```

### Web Rate Limits
```
login → 5 attempts per minute
password.reset → 5 attempts per minute
```

---

## 📊 Response Formats

### Success Response (JSON)
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Resource data
  }
}
```

### Error Response (JSON)
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### Pagination Response
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "total_pages": 10,
    "per_page": 15,
    "total": 150
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

---

## 🎨 Frontend Integration

### AJAX Requests
Most routes support both traditional form submission and AJAX:
```javascript
// AJAX request example
fetch('/tasks/1/complete', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    notes: 'Task completed successfully'
  })
})
.then(response => response.json())
.then(data => {
  // Handle response
});
```

### Form Submission
Traditional form with redirect:
```html
<form method="POST" action="/tasks/1/complete">
  @csrf
  <textarea name="notes"></textarea>
  <button type="submit">Complete</button>
</form>
```

---

## 🛡️ Security Headers

### CSRF Protection
All POST/PUT/DELETE requests require CSRF token:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### CORS Configuration
```php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['Content-Type', 'Authorization'],
```

---

## 📝 API Architecture

### Current Implementation
The system uses **Web Routes with AJAX** instead of traditional REST API:

**Advantages:**
- ✅ Integrated with Laravel's session authentication
- ✅ CSRF protection out of the box
- ✅ Supports both traditional forms and AJAX
- ✅ SSR (Server-Side Rendering) with Blade templates
- ✅ SEO-friendly for public pages
- ✅ Real-time updates via Livewire

**Routes Structure:**
```
routes/
├── web.php         → All application routes (500+ endpoints)
├── api.php         → Single Sanctum endpoint (/api/user)
├── channels.php    → Broadcasting channels
├── console.php     → Scheduled tasks
└── jetstream.php   → Jetstream authentication routes
```

### Future API Structure (Planned)
If REST API is needed for mobile apps or third-party integrations:
```
/api/v1/projects
/api/v1/tasks
/api/v1/users
/api/v1/attendance
```

---

**Routes Version**: 1.0  
**Last Updated**: 2024  
**Status**: Production Ready  
**Authentication**: Laravel Sanctum (Session-based for web)  
**Architecture**: Web Routes with AJAX Support

