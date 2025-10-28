# 📚 Arkan Project - Complete Documentation

## 📋 Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Technology Stack](#technology-stack)
4. [Core Modules](#core-modules)
5. [Database Models](#database-models)
6. [Services Layer](#services-layer)
7. [Routes Architecture](#routes-architecture)
8. [Authentication & Authorization](#authentication--authorization)
9. [Notification System](#notification-system)
10. [Scheduled Tasks](#scheduled-tasks)
11. [Features](#features)

---

## 🎯 Project Overview

**Arkan** is a comprehensive Enterprise Resource Planning (ERP) system built with Laravel, designed specifically for managing projects, tasks, employees, clients, and operations in a modern digital workspace.

### Key Capabilities:
- **Project Management**: Complete lifecycle management from creation to delivery
- **Task Management**: Assignment, tracking, revisions, and approvals
- **Employee Management**: Attendance, performance, evaluations, and gamification
- **Client Relationship Management (CRM)**: Client tracking, tickets, and communication
- **HR Management**: Absence requests, overtime, permissions, and salary sheets
- **Gamification**: Seasons, badges, points, and employee competitions
- **Real-time Notifications**: Firebase, Slack, and Database notifications
- **Time Tracking**: NTP-based accurate time tracking with automatic pause/resume

---

## 🏗️ System Architecture

### Architecture Pattern: **Service-Oriented Architecture (SOA)**

```
┌─────────────────────────────────────────────────────────────┐
│                        Web Interface                         │
│                      (Blade Templates)                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                       Controllers                            │
│  (75 Controllers - Route Handlers & Business Logic)         │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                     Services Layer                           │
│  (120+ Services - Business Logic & Domain Operations)       │
│  ├─ Project Management (27 services)                        │
│  ├─ Task Management (14 services)                           │
│  ├─ Notifications (16 services)                             │
│  ├─ Slack Integration (11 services)                         │
│  ├─ Dashboard & Analytics (8 services)                      │
│  └─ Other Domain Services                                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                    Models Layer                              │
│         (80 Eloquent Models - Data Access)                  │
│  ├─ Users, Teams, Roles                                     │
│  ├─ Projects, Tasks, Services                               │
│  ├─ Clients, Tickets, Call Logs                             │
│  ├─ Attendance, Requests, Reviews                           │
│  └─ Gamification (Badges, Seasons, Points)                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                      Database                                │
│                   (MySQL/MariaDB)                            │
└──────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                  External Integrations                       │
│  ├─ Firebase Cloud Messaging (FCM) - Push Notifications    │
│  ├─ Slack API - Team Communication                          │
│  ├─ Wasabi S3 - File Storage (Projects & Attachments)      │
│  ├─ Google Firestore - Document Storage                     │
│  └─ NTP Servers - Time Synchronization                      │
└─────────────────────────────────────────────────────────────┘
```

### Key Architectural Components:

1. **Policies Layer**: Authorization rules for all models (16 policies)
2. **Observers Layer**: Event-driven actions on model changes (13 observers)
3. **Middleware Layer**: Request filtering and authentication
4. **Queue System**: Asynchronous job processing (Firebase notifications, Slack messages)
5. **Broadcasting**: Real-time updates via WebSockets

---

## 🛠️ Technology Stack

### Backend Framework
- **Laravel 10.x** - PHP Framework
- **PHP 8.1+** - Programming Language
- **MySQL/MariaDB** - Relational Database

### Frontend
- **Blade Templates** - Server-side rendering
- **Livewire** - Dynamic UI components
- **Alpine.js** - Lightweight JavaScript framework
- **Tailwind CSS** - Utility-first CSS

### External Services
- **Firebase Cloud Messaging** - Push notifications
- **Slack API** - Team collaboration notifications
- **Wasabi S3** - Object storage (AWS S3 compatible)
- **Google Firestore** - NoSQL document database
- **NTP Protocol** - Time synchronization

### Key Laravel Packages
- **Laravel Jetstream** - Authentication scaffolding with teams
- **Spatie Laravel Permission** - Role and permission management
- **Spatie Laravel Activitylog** - Activity logging
- **PhpSpreadsheet** - Excel file processing
- **Google Cloud Firestore** - Firestore PHP SDK

---

## 🧩 Core Modules

### 1. **Project Management Module**
Complete project lifecycle management from initiation to delivery.

#### Key Features:
- Project creation with client and service selection
- Multi-service project support
- Team assignment with smart recommendations
- Status tracking (planning, in-progress, completed, cancelled)
- Delivery approvals (administrative & technical)
- Project analytics and dashboards
- Attachment management with S3 integration
- Project code generation
- Revision tracking and management

#### Components:
- **Models**: `Project`, `ProjectServiceUser`, `ProjectAttachment`, `ProjectTask`
- **Services**: 27 services in `ProjectManagement/` directory
- **Controllers**: `ProjectController`, `ProjectAnalyticsController`, `ProjectParticipantController`

#### Workflow:
```
1. Create Project → 2. Assign Services → 3. Add Team Members → 
4. Generate Tasks → 5. Track Progress → 6. Delivery Approval → 
7. Complete Project
```

---

### 2. **Task Management Module**
Comprehensive task assignment, tracking, and approval system.

#### Key Features:
- Task creation (regular & template-based)
- Task assignment to users
- Time tracking with automatic pause/resume
- Task revisions and approvals
- Task transfer between users (with points tracking)
- Task completion with delivery confirmation
- Administrative & technical approvals
- Task history and activity logs

#### Task Types:
- **Regular Tasks**: One-time tasks created manually
- **Template Tasks**: Reusable task templates
- **Additional Tasks**: Bonus tasks for points

#### Components:
- **Models**: `Task`, `TaskUser`, `TemplateTask`, `TemplateTaskUser`, `TaskRevision`
- **Services**: 14 services in `Tasks/` directory
- **Controllers**: `TaskController`, `TaskDeliveryController`, `TaskRevisionController`

#### Task Lifecycle:
```
Created → Assigned → In Progress → [Paused] → Completed → 
Pending Approval → Approved (Admin) → Approved (Technical) → Delivered
```

---

### 3. **Employee Management Module**
Complete HR and employee lifecycle management.

#### Sub-Modules:

##### 3.1 Attendance System
- Daily attendance tracking
- Check-in/check-out with NTP time
- Late arrival and early departure tracking
- Attendance reports and analytics
- Work shifts management

##### 3.2 Leave Management
- **Absence Requests**: Full-day leave requests
- **Permission Requests**: Partial-day leave (with return tracking)
- **Overtime Requests**: Extra work hours tracking
- Hierarchical approval workflow (Manager → HR)
- Status tracking (pending, approved, rejected)
- Remaining balance calculation

##### 3.3 Performance Management
- Employee evaluations (technical, marketing, coordination, customer service)
- KPI tracking and evaluation
- Performance analytics
- Monthly reviews
- Skill tracking

##### 3.4 Gamification System
- **Seasons**: Competitive periods with rankings
- **Badges**: Achievement-based rewards
- **Points**: Task-based scoring system
- **Demotion Rules**: Automatic rank adjustments
- **Leaderboards**: Real-time rankings

#### Components:
- **Models**: `User`, `Attendance`, `AbsenceRequest`, `PermissionRequest`, `OverTimeRequests`
- **Services**: Attendance, notification, and statistics services
- **Controllers**: `AttendanceController`, `AbsenceRequestController`, `PermissionRequestController`

---

### 4. **Client Relationship Management (CRM)**
Complete client interaction and ticket management system.

#### Features:
- Client database management
- Call log tracking
- Ticket system with priority levels
- Ticket assignments and comments
- Client interest tracking
- CRM dashboard with analytics
- Ticket status workflow

#### Ticket Workflow:
```
Open → [Assigned] → In Progress → [On Hold] → Resolved → Closed
```

#### Components:
- **Models**: `Client`, `CallLog`, `ClientTicket`, `TicketComment`, `ClientInterest`
- **Services**: CRM analytics and ticket management
- **Controllers**: `ClientController`, `ClientTicketController`, `CallLogController`

---

### 5. **Meeting Management Module**
Comprehensive meeting scheduling and approval system.

#### Features:
- Meeting creation (internal, client, training)
- Participant management
- Meeting approvals (for client meetings)
- Mentions in meeting notes
- Meeting history and notes
- Calendar integration

#### Components:
- **Models**: `Meeting`, `MeetingNote`, `MeetingParticipant`
- **Services**: Meeting notification services
- **Controllers**: `MeetingController`

---

## 🗄️ Database Models

### User & Authentication Models (7)
| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `User` | Core user entity | teams, roles, permissions, tasks, projects |
| `Team` | User teams/groups | owner, users, projects |
| `Role` | User roles | users, permissions |
| `Permission` | System permissions | roles |
| `RoleApproval` | Approval hierarchy | role, approverRole |
| `PersonalAccessToken` | API tokens | user |
| `Membership` | Team membership | user, team |

### Project Models (15)
| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Project` | Main project entity | client, services, tasks, participants |
| `ProjectServiceUser` | Project participants | project, user, service |
| `ProjectTask` | Project tasks | project, service |
| `ProjectAttachment` | File attachments | project, user, task |
| `AttachmentShare` | Shared attachments | attachment, sharedBy, sharedWith |
| `AttachmentShareAccess` | Access logs | attachmentShare, user |
| `ProjectRevision` | Project changes | project, service |
| `CompanyService` | Available services | projects |
| `ServicePointLimit` | Service points config | service |
| `ProjectPreparationPeriod` | Setup phase tracking | project |
| `ProjectCode` | Project codes | project |
| `ProjectAnalytic` | Analytics data | project |
| `ProjectNote` | Project notes | project, user |
| `ProjectLimit` | Project limits | - |
| `AttachmentConfirmation` | Attachment verifications | project, attachment |

### Task Models (12)
| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Task` | Regular tasks | project, creator, users |
| `TaskUser` | Task assignments | task, user |
| `TaskRevision` | Task revisions | task, user, approver |
| `TemplateTask` | Task templates | creator |
| `TemplateTaskUser` | Template assignments | templateTask, user, project |
| `GraphicTaskType` | Graphic task types | tasks |
| `TaskTransferHistory` | Task transfers | task, fromUser, toUser |
| `AdditionalTask` | Bonus tasks | creator |
| `AdditionalTaskUser` | Additional assignments | additionalTask, user |
| `TaskApprovalLog` | Approval history | task, approver |
| `TaskItem` | Task checklist items | task |
| `TaskLog` | Task activity logs | task, user |

### Employee & HR Models (18)
| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Attendance` | Daily attendance | user |
| `AbsenceRequest` | Leave requests | user |
| `PermissionRequest` | Permission requests | user |
| `OverTimeRequests` | Overtime requests | user |
| `WorkShift` | Work schedules | users |
| `EmployeeShift` | Shift assignments | user, shift |
| `SalarySheet` | Salary data | - |
| `EmployeeError` | Error tracking | user, createdBy |
| `Skill` | Employee skills | users |
| `UserSkill` | Skill assignments | user, skill |
| `Performance` | Performance reviews | user |
| `TechnicalReview` | Technical evaluations | user, reviewer |
| `MarketingReview` | Marketing evaluations | user, reviewer |
| `CoordinationReview` | Coordination evaluations | user, reviewer |
| `CustomerServiceReview` | CS evaluations | user, reviewer |
| `EvaluationCriteria` | Evaluation criteria | - |
| `KpiEvaluation` | KPI evaluations | user, evaluator |
| `RoleEvaluationMapping` | Role-evaluation mapping | role, criteria |

### CRM Models (8)
| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Client` | Client database | projects, tickets, callLogs |
| `ClientTicket` | Support tickets | client, creator, assignments |
| `TicketComment` | Ticket comments | ticket, user |
| `TicketAssignment` | Ticket assignments | ticket, user, assignedBy |
| `CallLog` | Call records | client, user |
| `ClientInterest` | Client interests | client |
| `Package` | Service packages | - |
| `PackageUser` | Package assignments | package, user |

### Gamification Models (8)
| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Season` | Competition periods | badges, demotionRules |
| `Badge` | Achievement badges | season, users |
| `UserBadge` | Badge awards | user, badge |
| `UserPoint` | Points tracking | user |
| `DemotionRule` | Rank demotion rules | season, badge |
| `UserDemotion` | Demotion records | user, rule |
| `EmployeeCompetition` | Competition data | user |
| `SeasonStatistic` | Season analytics | season |

### Notification Models (5)
| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Notification` | All notifications | user |
| `FirebaseNotification` | FCM notifications | user |
| `SlackNotification` | Slack messages | user |
| `EmailNotification` | Email messages | user |
| `NotificationLog` | Notification history | user |

### System Models (7)
| Model | Purpose | Key Relationships |
|-------|---------|-------------------|
| `ActivityLog` | Activity logging | user |
| `AuditLog` | Audit trail | user |
| `Setting` | System settings | - |
| `Department` | Departments | users |
| `SecureId` | Secure identifiers | - |
| `ServiceData` | Service metadata | service |
| `SpecialCase` | Special configurations | - |

**Total Models: 80**

---

## 🔧 Services Layer

The application uses a comprehensive service layer to encapsulate business logic.

### Service Categories:

#### 1. Project Management Services (27 services)
Located in: `app/Services/ProjectManagement/`

| Service | Purpose |
|---------|---------|
| `AttachmentService` | Wasabi S3 file management, presigned URLs |
| `AttachmentSharingService` | Secure file sharing with tokens |
| `AttachmentSharingNotificationService` | Share notifications (DB, Firebase, Slack) |
| `ProjectAnalyticsService` | Project metrics and analytics |
| `ProjectApprovalService` | Delivery approvals workflow |
| `ProjectAuthorizationService` | Access control checks |
| `ProjectCodeService` | Unique code generation |
| `ProjectCompletionService` | Project completion logic |
| `ProjectCRUDService` | Create, read, update, delete |
| `ProjectParticipantService` | Team member management |
| `ProjectRevisionService` | Change tracking |
| `ProjectSidebarService` | Sidebar data aggregation |
| `ProjectStatusService` | Status management |
| `ProjectStorageService` | Wasabi folder structure |
| `ProjectServiceStatusService` | Service status updates |
| `ProjectValidationService` | Deletion checks |
| `ProjectTeamRecommendationService` | Smart team suggestions |
| And 10 more... | |

#### 2. Task Management Services (14 services)
Located in: `app/Services/Tasks/` and `app/Services/TaskController/`

| Service | Purpose |
|---------|---------|
| `TaskService` | Core task operations |
| `TaskStorageService` | Task file storage |
| `TaskApprovalService` | Approval workflow |
| `TaskCompletionService` | Completion logic |
| `TaskRevisionService` | Revision management |
| `TaskTransferService` | Task transfers with points |
| `TaskNotificationService` | Task notifications |
| `TaskFilterService` | Advanced filtering |
| `TaskHierarchyService` | Permission-based queries |
| `TaskManagementService` | CRUD operations |
| And 4 more... | |

#### 3. Notification Services (16 services)
Located in: `app/Services/Notifications/`

| Service | Purpose |
|---------|---------|
| `AdditionalTaskNotificationService` | Additional task notifications |
| `DeliveryNotificationService` | Project delivery notifications |
| `TaskDeliveryNotificationService` | Task delivery notifications |
| `EmployeeNotificationService` | Employee absence notifications |
| `EmployeePermissionNotificationService` | Permission notifications |
| `ManagerNotificationService` | Manager notifications (absences) |
| `ManagerPermissionNotificationService` | Manager permission notifications |
| `OvertimeEmployeeNotificationService` | Overtime employee notifications |
| `OvertimeManagerNotificationService` | Overtime manager notifications |
| `MeetingNotificationService` | Meeting notifications |
| `ProjectNotificationService` | Project creation notifications |
| `TicketNotificationService` | Ticket notifications |
| `ReviewNotificationService` | Review notifications |
| And 3 notification traits | |

#### 4. Slack Integration Services (11 services)
Located in: `app/Services/Slack/`

| Service | Purpose |
|---------|---------|
| `BaseSlackService` | Base Slack functionality |
| `ProjectSlackService` | Project Slack messages |
| `TaskSlackService` | Task Slack messages |
| `MeetingSlackService` | Meeting Slack messages |
| `RequestSlackService` | Request Slack messages |
| `RevisionSlackService` | Revision Slack messages |
| `TicketSlackService` | Ticket Slack messages |
| `AdditionalTaskSlackService` | Additional task Slack messages |
| `EmployeeErrorSlackService` | Error Slack messages |
| `AttachmentConfirmationSlackService` | Confirmation Slack messages |
| `TaskTransferSlackService` | Transfer Slack messages |

#### 5. Dashboard & Analytics Services (8 services)
Located in: `app/Services/ProjectDashboard/`

| Service | Purpose |
|---------|---------|
| `ProjectStatsService` | Project statistics |
| `TaskStatsService` | Task statistics |
| `RevisionStatsService` | Revision statistics |
| `EmployeePerformanceService` | Employee metrics |
| `TeamService` | Team analytics |
| `DepartmentService` | Department analytics |
| `TimeCalculationService` | Time calculations |
| `DateFilterService` | Date filtering |

#### 6. Request Management Services (7 services)
Located in: `app/Services/PermissionRequest/`

| Service | Purpose |
|---------|---------|
| `PermissionRequestService` | Core permission logic |
| `RequestCreationService` | Request creation |
| `RequestUpdateService` | Request updates |
| `RequestQueryService` | Data retrieval |
| `RequestValidationService` | Validation logic |
| `RequestStatusService` | Status management |
| `RequestStatisticsService` | Statistics |

#### 7. Employee Error Management (6 services)
Located in: `app/Services/EmployeeErrorController/`

| Service | Purpose |
|---------|---------|
| `EmployeeErrorManagementService` | Error CRUD operations |
| `EmployeeErrorIndexService` | Listing and filtering |
| `EmployeeErrorFilterService` | Advanced filtering |
| `EmployeeErrorStatisticsService` | Error analytics |
| `EmployeeErrorNotificationService` | Error notifications |
| `EmployeeErrorValidationService` | Validation |

#### 8. Core Services (18 services)

| Service | Purpose |
|---------|---------|
| `AbsenceRequestService` | Absence logic |
| `OverTimeRequestService` | Overtime logic |
| `AttendanceReportService` | Attendance reports |
| `BadgeService` | Badge management |
| `ChatService` | Chat functionality |
| `ExcelProcessingService` | Excel file processing |
| `FirebaseNotificationService` | FCM push notifications |
| `FirestoreService` | Firestore integration |
| `SalarySheetService` | Salary processing |
| `SalaryNotificationService` | Salary notifications |
| `SalaryEmailService` | Salary emails |
| `SeasonStatisticsService` | Season analytics |
| `SecureIdService` | Secure ID generation |
| `ServiceDataManagementService` | Service data management |
| `SlackNotificationService` | Core Slack service |
| `ViolationService` | Violation tracking |
| `ProjectPointsValidationService` | Points validation |
| `ReviewNotificationService` | Review notifications |

**Total Services: 120+**

---

## 🌐 Routes Architecture

### ⚠️ Important: Web Routes with AJAX, NOT REST API

Arkan ERP uses **Laravel's traditional monolithic architecture** with web routes and AJAX, not a separate REST API.

### Routes Structure

```
routes/
├── web.php         → ALL application routes (1107 lines, 500+ endpoints)
├── api.php         → Only 1 endpoint: GET /api/user
├── channels.php    → Broadcasting channels (1 channel)
├── console.php     → Scheduled tasks (6 tasks)
└── jetstream.php   → Jetstream authentication routes
```

### Single API Endpoint (`routes/api.php`)

```php
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

### Web Routes with AJAX (`routes/web.php`)

All 500+ endpoints are defined as web routes that support:
- ✅ Traditional form submission (POST with redirect)
- ✅ AJAX requests (return JSON when `Accept: application/json`)
- ✅ Session-based authentication
- ✅ CSRF protection
- ✅ Server-side rendering with Blade

**Example - AJAX Usage:**
```javascript
// All routes support AJAX with proper headers
fetch('/projects', {
  headers: {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').content
  }
})
```

### Why This Architecture?

**Advantages:**
1. **Better Security**: CSRF protection, session management
2. **SEO Friendly**: Server-side rendering for search engines
3. **Simplified Development**: No API versioning, no CORS issues
4. **Laravel Ecosystem**: Full Livewire, Blade, and Jetstream integration
5. **Performance**: No additional HTTP layer for API

**Use Cases:**
- Internal business applications
- Monolithic applications with complex UI
- When mobile app is not primary focus
- When SEO matters for some pages

---

## 🔐 Authentication & Authorization

### Authentication System
- **Package**: Laravel Jetstream with Livewire
- **Features**: 
  - User registration and login
  - Email verification
  - Two-factor authentication (2FA)
  - Password reset
  - Profile management
  - Team management

### Authorization System
- **Package**: Spatie Laravel Permission
- **Components**:
  - Roles (dynamic role management)
  - Permissions (granular access control)
  - Role hierarchy for approvals

### Key Roles:
- **Admin**: Full system access
- **Manager**: Department/team management
- **HR**: Human resources operations
- **Operation Assistant**: Project setup
- **Employee**: Basic user access
- **Client Manager**: CRM access

### Authorization Policies (16 policies)
Each model has a policy class defining access rules:
- `ProjectPolicy`
- `TaskPolicy`
- `AttendancePolicy`
- `AbsenceRequestPolicy`
- `ClientPolicy`
- `MeetingPolicy`
- And 10 more...

### Approval Workflow System
**RoleApproval Model** defines hierarchical approval rules:
- Administrative approvals
- Technical approvals
- Conditional rules:
  - `requires_same_project`: Approver must be in same project
  - `requires_team_owner`: Approver must be team owner

---

## 🔔 Notification System

### Multi-Channel Notifications

#### 1. Database Notifications
- Stored in `notifications` table
- Accessible via user dashboard
- Real-time badge counts
- Notification types:
  - `task_assigned`
  - `task_approved`
  - `task_revision`
  - `project_participant_added`
  - `meeting_created`
  - `ticket_assigned`
  - `absence_request_status`
  - And 30+ more types...

#### 2. Firebase Cloud Messaging (FCM)
- Push notifications to mobile/web
- Queue-based processing
- Retry logic for failed deliveries
- Notification data includes:
  - Title
  - Message
  - Deep link URL
  - Type for routing

**Service**: `FirebaseNotificationService`
- `sendNotification()`: Direct send
- `sendNotificationQueued()`: Queue-based send
- `sendBulkNotifications()`: Batch processing

#### 3. Slack Notifications
- Team channel notifications
- Direct messages to users
- Rich formatted messages with:
  - Color-coded attachments
  - Action buttons
  - Contextual information
  - Timestamps

**Channels**:
- HR Channel (for requests and approvals)
- Project Channel (for project updates)
- Error Channel (for system errors)
- Direct Messages (for personal notifications)

#### 4. Email Notifications
- Salary sheet notifications
- Important system alerts
- Password reset
- Account verification

### Notification Traits
- `HasFirebaseNotification`: Firebase sending logic
- `HasSlackNotification`: Slack HR channel logic
- `HasReviewSlackNotification`: Review-specific Slack logic

### Notification Flow Example: Task Completion
```
Task Completed
    ├─> Database Notification (to approvers)
    ├─> Firebase Notification (push to approver's device)
    └─> Slack Notification (to HR channel & approver DM)
```

---

## ⏰ Scheduled Tasks

Located in: `routes/console.php`

### Scheduled Commands:

| Schedule | Command | Purpose |
|----------|---------|---------|
| Every Minute | `attendance:create-daily` | Create daily attendance records |
| Every Minute | `check:birthdays` | Check and notify birthdays |
| Every Minute | `check:contracts` | Check contract expirations |
| Daily at 1:00 PM | `tasks:pause-running --time=1pm` | Pause tasks during break time |
| Daily at 5:00 PM | `tasks:pause-running --time=5pm` | Pause tasks at end of work |
| Hourly | `inspire` | Laravel's inspire command |

### Time Management
- **Timezone**: Africa/Cairo (GMT+2)
- **NTP Integration**: Accurate time synchronization
- **Automatic Pausing**: Tasks auto-pause during:
  - Break time (1:00 PM)
  - End of work day (5:00 PM)

---

## ✨ Features

### 1. Project Management Features

#### Project Creation & Setup
- Multi-service project support
- Client assignment
- Service selection from company services
- Start and delivery date planning
- Project code auto-generation

#### Team Management
- Smart team recommendations based on workload
- Multi-member assignment per service
- Role-based access within projects
- Participant addition/removal with notifications

#### File Management
- Wasabi S3 integration for scalable storage
- Presigned URLs for secure file access
- Attachment sharing with:
  - Time-limited access tokens
  - Password protection option
  - Access tracking and logs
  - Expiration notifications
- Attachment replies (nested attachments)
- Attachment confirmations

#### Project Status Tracking
- Status: Planning, In Progress, Completed, Cancelled, Paused
- Automatic status updates based on task completion
- Service-level status tracking
- Progress percentage calculation

#### Delivery & Approval
- Two-level approval system:
  - Administrative approval
  - Technical approval
- Hierarchical approval workflow
- Approval notes and rejection reasons
- Automatic notifications to all parties

#### Project Analytics
- Task completion rates
- Team performance metrics
- Time tracking per project
- Revision statistics
- Service-wise breakdowns

---

### 2. Task Management Features

#### Task Types
- **Regular Tasks**: Standard one-time tasks
- **Template Tasks**: Reusable task templates
- **Additional Tasks**: Bonus tasks for gamification

#### Task Assignment & Tracking
- Multi-user assignment
- Priority levels
- Due dates and deadlines
- Estimated vs actual time tracking
- Task dependencies

#### Time Tracking
- NTP-based accurate time logging
- Automatic pause/resume on:
  - User going offline
  - Break time (1 PM)
  - End of work (5 PM)
  - Task switching
- Manual pause/resume controls
- Time split calculation for concurrent tasks

#### Task Revisions
- Revision request submission
- Multi-level revision approval
- Revision notes and attachments
- Revision history tracking
- Points deduction for revisions

#### Task Transfer
- Transfer tasks between users
- Positive vs negative transfers
- Points tracking:
  - Positive transfer: Points awarded to both users
  - Negative transfer: Points deducted from transferrer
- Transfer reason documentation
- Transfer history logs

#### Task Approval
- Administrative approval
- Technical approval
- Conditional approvals based on:
  - User role
  - Project membership
  - Team ownership
- Approval notifications to all stakeholders

---

### 3. Employee Management Features

#### Attendance System
- **Check-in/Check-out**:
  - NTP-based time logging
  - Location tracking (optional)
  - Photo capture on check-in
- **Attendance Status**:
  - On time
  - Late arrival (with minutes)
  - Early departure (with minutes)
  - Absent
- **Reports**:
  - Daily attendance reports
  - Monthly summaries
  - Late/early statistics
  - Attendance percentage

#### Leave Management

##### Absence Requests
- Full-day leave requests
- Date and reason specification
- Attachment support (medical certificates)
- Two-level approval (Manager → HR)
- Status tracking
- Remaining balance display

##### Permission Requests
- Partial-day leave (hours/minutes)
- Departure and return time
- Return confirmation tracking
- Minute-based balance management
- Automatic balance deduction

##### Overtime Requests
- Extra work hours logging
- Date and time range
- Overtime reason
- Two-level approval
- Overtime balance tracking

#### Approval Workflow
```
Employee Request → Manager Review → HR Review → Final Status
                     (Approve/Reject)  (Approve/Reject)
```
- Notifications at each stage
- Rejection reasons
- Status modification by HR
- Reset functionality

#### Performance Management

##### Employee Evaluations
Four evaluation types:
1. **Technical Review**
   - Code quality
   - Problem-solving
   - Technical skills
   - Learning ability

2. **Marketing Review**
   - Campaign performance
   - Content quality
   - Social media engagement
   - Lead generation

3. **Coordination Review**
   - Communication skills
   - Team collaboration
   - Time management
   - Organization

4. **Customer Service Review**
   - Response time
   - Customer satisfaction
   - Problem resolution
   - Communication quality

##### KPI Evaluations
- Goal setting
- Progress tracking
- Score calculation
- Periodic reviews

##### Skills Management
- Skill database
- Skill assignment to users
- Proficiency levels
- Skill gap analysis

---

### 4. Gamification System

#### Seasons
- Defined time periods for competition
- Season start and end dates
- Season-specific badges
- Season rankings and leaderboards

#### Badges
- Achievement-based badges
- Point thresholds for each badge
- Badge icons and colors
- Badge progression levels

#### Points System
- **Earning Points**:
  - Task completion
  - Positive task transfers
  - Additional tasks
  - Performance bonuses
- **Losing Points**:
  - Task revisions
  - Negative task transfers
  - Errors and violations
  - Late attendance

#### Demotion Rules
- Automatic badge demotion
- Conditions:
  - Task revision count
  - Negative transfers
  - Error count
  - Attendance violations
- Grace period settings
- Demotion notifications

#### Leaderboards
- Real-time rankings
- Season-based competition
- Department-wise rankings
- Team rankings

---

### 5. CRM Features

#### Client Management
- Client database with:
  - Contact information
  - Company details
  - Industry classification
  - Client status
- Client history tracking
- Client notes

#### Call Logs
- Call recording logs
- Call type (incoming/outgoing)
- Call duration
- Call notes and outcomes
- Follow-up scheduling

#### Ticket System

##### Ticket Creation
- Ticket types: Technical, Billing, General
- Priority levels: Low, Medium, High, Urgent
- Department routing
- Client association

##### Ticket Assignment
- Multi-user assignment
- Assignment notifications
- Workload balancing
- Assignment history

##### Ticket Comments
- Comment threads
- @mentions in comments
- Mention notifications
- Comment type (note, update, resolution)
- Attachment support

##### Ticket Workflow
```
Open → Assigned → In Progress → [On Hold] → Resolved → Closed
```
- Status transitions
- Resolution notes
- Close confirmation

#### Client Interests
- Interest tracking
- Interest level
- Follow-up dates
- Conversion tracking

---

### 6. Meeting Management Features

#### Meeting Creation
- Meeting types:
  - Internal meetings
  - Client meetings (require approval)
  - Training sessions
- Meeting details:
  - Title and description
  - Start and end time
  - Location (physical/virtual)
  - Agenda

#### Participant Management
- Add multiple participants
- Participant roles
- RSVP tracking
- Participant notifications

#### Meeting Approvals
- Client meetings require approval
- Approval workflow:
  - Request submission
  - Approver notification
  - Approval with notes
  - Time modification option
  - Rejection with reason

#### Meeting Notes
- Note creation during/after meeting
- @mentions in notes
- Note attachments
- Note history

#### Meeting Mentions
- @mention participants in description
- @mention in notes
- @everyone for all participants
- Mention notifications

---

### 7. File Storage & Management

#### Wasabi S3 Integration
- Scalable object storage
- Project folder structure:
  ```
  projects/
    └── {project_code}/
        ├── {service_id}/
        │   ├── project/
        │   │   └── {attachments}
        │   └── tasks/
        │       └── {task_id}/
        │           └── {attachments}
        └── general/
            └── {attachments}
  ```

#### Presigned URLs
- Temporary signed URLs for secure access
- Upload presigned URLs (PUT)
- Download presigned URLs (GET)
- Configurable expiration (1 hour default)

#### Attachment Sharing
- Generate secure sharing tokens
- Share options:
  - Expiration date
  - Password protection
  - Access limit
- Share tracking:
  - Access logs
  - Last accessed time
  - Access count
- Share notifications:
  - Share creation
  - File accessed
  - Share expired
  - Share cancelled

#### File Types
- Documents (PDF, DOCX, XLSX, etc.)
- Images (JPG, PNG, GIF, etc.)
- Videos (MP4, AVI, etc.)
- Archives (ZIP, RAR, etc.)
- Code files (PHP, JS, CSS, etc.)

---

### 8. Real-time Features

#### Broadcasting
- WebSocket integration via Laravel Echo
- Private channels per user
- Real-time notifications
- Real-time presence

#### Live Updates
- Task status changes
- New notifications
- Attendance updates
- Project updates

---

### 9. Reporting & Analytics

#### Dashboard Types
1. **User Dashboard**
   - Personal statistics
   - My tasks
   - My projects
   - Recent activity

2. **HR Dashboard**
   - Attendance overview
   - Request pending approvals
   - Employee statistics
   - Leave balances

3. **CRM Dashboard**
   - Ticket statistics
   - Client activity
   - Call logs summary
   - Conversion rates

4. **Project Dashboard**
   - Project overview
   - Task distribution
   - Team performance
   - Timeline view

#### Reports
- Attendance reports
- Performance reports
- Project reports
- Employee reports
- Custom date ranges
- Export to Excel

---

### 10. Security Features

#### Data Security
- Encrypted sensitive data
- Secure password hashing
- API token management
- Rate limiting

#### Access Control
- Role-based access control (RBAC)
- Policy-based authorization
- Middleware protection
- Secure routes

#### Audit Trail
- Activity logging via Spatie Activity Log
- Audit logs for critical actions
- Change history tracking
- User action logs

#### Data Privacy
- GDPR compliance features
- Data deletion policies
- Privacy settings
- Consent management

---

### 11. Integration Features

#### Firebase Integration
- Cloud Messaging for push notifications
- Firestore for document storage
- Analytics tracking
- Performance monitoring

#### Slack Integration
- Workspace integration
- Channel notifications
- Direct messages
- Rich message formatting
- Slash commands support

#### External APIs
- NTP time servers
- Email services (SMTP)
- SMS gateways (optional)
- Payment gateways (future)

---

## 🚀 Advanced Features

### 1. Smart Recommendations
- **Team Recommendations**: AI-based team suggestions considering:
  - Current workload
  - Team member availability
  - Skills match
  - Past performance

### 2. Automatic Time Management
- **Task Auto-Pause**: Pauses running tasks when:
  - User checks out
  - Break time arrives
  - Work day ends
- **NTP Synchronization**: Ensures accurate time across all clients

### 3. Hierarchical Approval System
- **Dynamic Approval Routes**: Based on:
  - User role
  - Project membership
  - Team ownership
- **Conditional Rules**: Flexible approval requirements

### 4. Activity Tracking
- **Comprehensive Logs**: Tracks:
  - Model changes
  - User actions
  - System events
- **Auditing**: Full audit trail for compliance

### 5. Queue System
- **Asynchronous Processing**: For:
  - Firebase notifications
  - Slack messages
  - Email sending
  - Heavy computations
- **Job Retry**: Automatic retry on failure

---

## 📊 System Statistics

### Code Metrics
- **Total Models**: 80
- **Total Services**: 120+
- **Total Controllers**: 75
- **Total Policies**: 16
- **Total Observers**: 13
- **Total Middleware**: 10+
- **Web Routes**: 500+ endpoints (in `routes/web.php`)
- **API Routes**: 1 endpoint only (in `routes/api.php`)
- **Routes File Size**: 1107 lines (`web.php`)

### Features Count
- **Main Modules**: 7 major modules
- **Sub-Modules**: 25+ sub-modules
- **Notification Types**: 40+ types
- **Scheduled Jobs**: 6 jobs
- **Integration Points**: 5 external services

---

## 🎨 Frontend Technologies

### CSS Frameworks & Files
- **Main Styles**: `app.css`
- **Module-Specific CSS**: 60+ CSS files organized by feature
- **Utility Framework**: Tailwind CSS
- **Icons**: Font Awesome, Heroicons

### JavaScript
- **Main Scripts**: `app.js`
- **Module-Specific JS**: 80+ JavaScript files
- **Framework**: Alpine.js for reactivity
- **Charts**: Chart.js for analytics

### UI Components
- **Blade Components**: Reusable UI components
- **Livewire Components**: Dynamic components
- **Toast Notifications**: Real-time feedback
- **Modals**: Confirmation and form dialogs

---

## 🔧 Configuration & Environment

### Required Environment Variables

```env
# Application
APP_NAME=Arkan
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arkan_db
DB_USERNAME=db_user
DB_PASSWORD=db_password

# Firebase
FIREBASE_CREDENTIALS=path/to/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVER_KEY=your-server-key

# Slack
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
SLACK_REVIEW_WEBHOOK_URL=https://hooks.slack.com/services/...
SLACK_BOT_TOKEN=xoxb-...

# Wasabi S3
WASABI_ACCESS_KEY_ID=...
WASABI_SECRET_ACCESS_KEY=...
WASABI_BUCKET=your-bucket-name
WASABI_REGION=us-east-1
WASABI_ENDPOINT=https://s3.wasabisys.com

# Google Firestore
GOOGLE_APPLICATION_CREDENTIALS=path/to/firestore-credentials.json

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@arkan.com
MAIL_FROM_NAME="${APP_NAME}"

# Queue
QUEUE_CONNECTION=database

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Broadcasting
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=mt1
```

---

## 📝 Best Practices Implemented

### Code Organization
✅ Service-oriented architecture  
✅ Repository pattern for data access  
✅ Policy-based authorization  
✅ Observer pattern for event handling  
✅ Dependency injection  
✅ Interface segregation  

### Performance
✅ Query optimization  
✅ Eager loading relationships  
✅ Caching strategies  
✅ Queue-based async processing  
✅ Database indexing  

### Security
✅ CSRF protection  
✅ XSS prevention  
✅ SQL injection prevention  
✅ Rate limiting  
✅ Secure file uploads  
✅ Encrypted sensitive data  

### Code Quality
✅ PSR-12 coding standards  
✅ Comprehensive comments  
✅ Error handling  
✅ Logging and monitoring  
✅ Validation rules  

---

## 🎯 Future Enhancements

### Planned Features
- [ ] Mobile apps (iOS & Android)
- [ ] Advanced AI recommendations
- [ ] Video conferencing integration
- [ ] Advanced analytics dashboard
- [ ] API documentation (Swagger/OpenAPI)
- [ ] Multi-language support
- [ ] Dark mode
- [ ] Advanced reporting tools
- [ ] Integration with more third-party services
- [ ] Automated testing suite

---

## 📞 Support & Maintenance

### System Requirements
- **PHP**: 8.1 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.3 or higher
- **Node.js**: 16.x or higher (for asset compilation)
- **Composer**: 2.x
- **Redis**: (optional, for queue and cache)

### Deployment
- **Web Server**: Nginx / Apache
- **Process Manager**: Supervisor (for queue workers)
- **SSL**: Required for production
- **Backup**: Regular database and file backups

---

## 📄 License & Credits

### Built With
- **Laravel Framework**: PHP web application framework
- **Laravel Jetstream**: Authentication scaffolding
- **Spatie Packages**: Permission and activity logging
- **Livewire**: Dynamic UI components
- **Alpine.js**: Lightweight JavaScript framework
- **Tailwind CSS**: Utility-first CSS framework

---

## 📚 Additional Resources

### Documentation Links
- Laravel: https://laravel.com/docs
- Laravel Jetstream: https://jetstream.laravel.com
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Firebase: https://firebase.google.com/docs
- Slack API: https://api.slack.com
- Wasabi: https://wasabi.com/s3-compatible-cloud-storage

---

**Documentation Version**: 1.0  
**Last Updated**: 2024  
**Project**: Arkan ERP System  
**Status**: Production Ready

---

*This documentation provides a comprehensive overview of the Arkan ERP system. For specific implementation details, refer to the inline code comments and individual service/controller documentation.*

