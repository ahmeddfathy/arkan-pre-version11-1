# 🗄️ Arkan ERP - Database Schema Documentation

## Overview
This document provides detailed information about the database structure, relationships, and key fields for the Arkan ERP system.

---

## 📊 Database Statistics
- **Total Tables**: 80+ tables
- **Database Engine**: InnoDB (MySQL/MariaDB)
- **Character Set**: utf8mb4_unicode_ci
- **Relationships**: 200+ foreign key constraints
- **Indexes**: Optimized for query performance

---

## 🔑 Core Tables

### 1. Users & Authentication

#### `users` Table
Primary user entity for the system.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(255) | User full name |
| `email` | VARCHAR(255) | Email address (unique) |
| `email_verified_at` | TIMESTAMP | Email verification time |
| `password` | VARCHAR(255) | Hashed password |
| `two_factor_secret` | TEXT | 2FA secret key |
| `two_factor_recovery_codes` | TEXT | 2FA recovery codes |
| `profile_photo_path` | VARCHAR(2048) | Profile photo URL |
| `current_team_id` | BIGINT UNSIGNED | Active team |
| `employee_status` | ENUM | active, inactive, suspended |
| `fcm_token` | VARCHAR(255) | Firebase token |
| `slack_user_id` | VARCHAR(50) | Slack user ID |
| `ntp_offset` | INTEGER | NTP time offset |
| `department_id` | BIGINT UNSIGNED | Department FK |
| `birthdate` | DATE | Birthday date |
| `hire_date` | DATE | Hire date |
| `contract_end_date` | DATE | Contract end |
| `phone` | VARCHAR(20) | Phone number |
| `address` | TEXT | Address |
| `emergency_contact` | JSON | Emergency contacts |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`email`)
- INDEX (`current_team_id`)
- INDEX (`department_id`)
- INDEX (`employee_status`)

**Relationships:**
- `teams` - many-to-many through `team_user`
- `roles` - many-to-many through `model_has_roles`
- `permissions` - many-to-many through `model_has_permissions`
- `tasks` - one-to-many through `task_user`
- `projects` - one-to-many through `project_service_user`

---

### 2. Project Management

#### `projects` Table
Main project entity.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `secure_id` | VARCHAR(50) | Secure identifier |
| `name` | VARCHAR(255) | Project name |
| `code` | VARCHAR(50) | Unique project code |
| `client_id` | BIGINT UNSIGNED | Client FK |
| `status` | ENUM | planning, in_progress, completed, cancelled, paused |
| `priority` | ENUM | low, medium, high, urgent |
| `start_date` | DATE | Project start |
| `end_date` | DATE | Project end |
| `team_delivery_date` | DATE | Team estimate |
| `client_agreed_delivery_date` | DATE | Agreed delivery |
| `actual_delivery_date` | DATE | Actual delivery |
| `preparation_days` | INTEGER | Preparation period |
| `description` | TEXT | Project details |
| `notes` | TEXT | Internal notes |
| `budget` | DECIMAL(12,2) | Project budget |
| `total_points` | INTEGER | Total points |
| `completion_percentage` | DECIMAL(5,2) | Progress % |
| `is_archived` | BOOLEAN | Archive status |
| `created_by` | BIGINT UNSIGNED | Creator user |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update |
| `deleted_at` | TIMESTAMP | Soft delete |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`code`)
- UNIQUE KEY (`secure_id`)
- INDEX (`client_id`)
- INDEX (`status`)
- INDEX (`created_by`)
- INDEX (`start_date`, `end_date`)

**Relationships:**
- `client` - belongs-to `clients`
- `services` - many-to-many through `project_service_user`
- `tasks` - one-to-many `project_tasks`
- `attachments` - one-to-many `project_attachments`
- `participants` - many-to-many users through `project_service_user`

---

#### `project_service_user` Table
Project participants and their services.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `project_id` | BIGINT UNSIGNED | Project FK |
| `service_id` | BIGINT UNSIGNED | Service FK |
| `user_id` | BIGINT UNSIGNED | User FK |
| `points` | INTEGER | Points for this service |
| `role` | VARCHAR(50) | User role in project |
| `delivered_at` | TIMESTAMP | Delivery time |
| `administrative_approved_at` | TIMESTAMP | Admin approval time |
| `administrative_approved_by` | BIGINT UNSIGNED | Admin approver |
| `administrative_notes` | TEXT | Admin notes |
| `technical_approved_at` | TIMESTAMP | Technical approval time |
| `technical_approved_by` | BIGINT UNSIGNED | Technical approver |
| `technical_notes` | TEXT | Technical notes |
| `is_active` | BOOLEAN | Active status |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`project_id`, `service_id`)
- INDEX (`user_id`)
- INDEX (`delivered_at`)
- UNIQUE KEY (`project_id`, `service_id`, `user_id`)

---

#### `project_attachments` Table
File attachments for projects.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `project_id` | BIGINT UNSIGNED | Project FK |
| `service_id` | BIGINT UNSIGNED | Service FK |
| `task_id` | BIGINT UNSIGNED | Task FK (nullable) |
| `parent_attachment_id` | BIGINT UNSIGNED | Parent for replies |
| `user_id` | BIGINT UNSIGNED | Uploader |
| `file_path` | VARCHAR(500) | S3 file path |
| `file_name` | VARCHAR(255) | Original filename |
| `file_size` | BIGINT | File size (bytes) |
| `file_type` | VARCHAR(100) | MIME type |
| `description` | TEXT | File description |
| `is_confirmed` | BOOLEAN | Confirmation status |
| `confirmed_at` | TIMESTAMP | Confirmation time |
| `confirmed_by` | BIGINT UNSIGNED | Confirmer user |
| `upload_status` | ENUM | pending, uploading, completed, failed |
| `created_at` | TIMESTAMP | Upload time |
| `updated_at` | TIMESTAMP | Last update |
| `deleted_at` | TIMESTAMP | Soft delete |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`project_id`, `service_id`)
- INDEX (`task_id`)
- INDEX (`user_id`)
- INDEX (`parent_attachment_id`)
- INDEX (`upload_status`)

---

#### `attachment_shares` Table
Secure file sharing.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `attachment_id` | BIGINT UNSIGNED | Attachment FK |
| `shared_by` | BIGINT UNSIGNED | Sharer user |
| `shared_with` | BIGINT UNSIGNED | Recipient user |
| `token` | VARCHAR(255) | Unique share token |
| `password` | VARCHAR(255) | Optional password |
| `expires_at` | TIMESTAMP | Expiration time |
| `access_limit` | INTEGER | Max accesses |
| `access_count` | INTEGER | Current accesses |
| `last_accessed_at` | TIMESTAMP | Last access |
| `is_cancelled` | BOOLEAN | Cancel status |
| `cancelled_at` | TIMESTAMP | Cancel time |
| `cancelled_by` | BIGINT UNSIGNED | Canceller |
| `created_at` | TIMESTAMP | Share creation |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`token`)
- INDEX (`attachment_id`)
- INDEX (`shared_by`, `shared_with`)
- INDEX (`expires_at`)

---

### 3. Task Management

#### `tasks` Table
Regular tasks.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `secure_id` | VARCHAR(50) | Secure ID |
| `name` | VARCHAR(255) | Task name |
| `description` | TEXT | Task details |
| `project_id` | BIGINT UNSIGNED | Project FK (nullable) |
| `service_id` | BIGINT UNSIGNED | Service FK (nullable) |
| `graphic_task_type_id` | BIGINT UNSIGNED | Task type FK |
| `priority` | ENUM | low, medium, high, urgent |
| `status` | ENUM | pending, in_progress, completed, cancelled |
| `estimated_hours` | DECIMAL(8,2) | Estimate |
| `points` | INTEGER | Points value |
| `due_date` | DATE | Deadline |
| `created_by` | BIGINT UNSIGNED | Creator |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update |
| `deleted_at` | TIMESTAMP | Soft delete |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`secure_id`)
- INDEX (`project_id`)
- INDEX (`created_by`)
- INDEX (`status`)
- INDEX (`due_date`)

---

#### `task_user` Table
Task assignments.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `task_id` | BIGINT UNSIGNED | Task FK |
| `user_id` | BIGINT UNSIGNED | Assigned user |
| `assigned_by` | BIGINT UNSIGNED | Assigner |
| `status` | ENUM | not_started, in_progress, paused, completed, cancelled |
| `actual_hours` | DECIMAL(8,2) | Time spent |
| `start_time` | TIMESTAMP | Start time |
| `end_time` | TIMESTAMP | End time |
| `completed_at` | TIMESTAMP | Completion |
| `pause_count` | INTEGER | # of pauses |
| `administrative_approved_at` | TIMESTAMP | Admin approval |
| `administrative_approved_by` | BIGINT UNSIGNED | Admin approver |
| `administrative_notes` | TEXT | Admin notes |
| `technical_approved_at` | TIMESTAMP | Tech approval |
| `technical_approved_by` | BIGINT UNSIGNED | Tech approver |
| `technical_notes` | TEXT | Tech notes |
| `points_earned` | INTEGER | Points gained |
| `created_at` | TIMESTAMP | Assignment |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`task_id`)
- INDEX (`user_id`)
- INDEX (`status`)
- UNIQUE KEY (`task_id`, `user_id`)

---

#### `task_revisions` Table
Task revision requests.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `task_id` | BIGINT UNSIGNED | Task FK |
| `user_id` | BIGINT UNSIGNED | Requester |
| `reason` | TEXT | Revision reason |
| `notes` | TEXT | Additional notes |
| `status` | ENUM | pending, approved, rejected |
| `approved_by` | BIGINT UNSIGNED | Approver |
| `approved_at` | TIMESTAMP | Approval time |
| `rejection_reason` | TEXT | Rejection reason |
| `points_deducted` | INTEGER | Points lost |
| `created_at` | TIMESTAMP | Request time |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`task_id`)
- INDEX (`user_id`)
- INDEX (`status`)

---

### 4. Employee & HR

#### `attendances` Table
Daily attendance records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | User FK |
| `date` | DATE | Attendance date |
| `check_in` | TIMESTAMP | Check-in time |
| `check_out` | TIMESTAMP | Check-out time |
| `status` | ENUM | present, absent, late, early_departure |
| `late_minutes` | INTEGER | Minutes late |
| `early_minutes` | INTEGER | Minutes early |
| `work_hours` | DECIMAL(8,2) | Total hours |
| `check_in_location` | POINT | GPS location |
| `check_out_location` | POINT | GPS location |
| `check_in_photo` | VARCHAR(500) | Photo path |
| `notes` | TEXT | Notes |
| `created_at` | TIMESTAMP | Creation |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`user_id`, `date`)
- INDEX (`date`)
- INDEX (`status`)

---

#### `absence_requests` Table
Leave requests.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | User FK |
| `absence_date` | DATE | Leave date |
| `reason` | TEXT | Leave reason |
| `attachment` | VARCHAR(500) | Supporting doc |
| `status` | ENUM | pending, approved, rejected, cancelled |
| `manager_status` | ENUM | pending, approved, rejected |
| `manager_notes` | TEXT | Manager notes |
| `manager_responded_at` | TIMESTAMP | Manager response |
| `hr_status` | ENUM | pending, approved, rejected |
| `hr_notes` | TEXT | HR notes |
| `hr_responded_at` | TIMESTAMP | HR response |
| `created_at` | TIMESTAMP | Request time |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`user_id`)
- INDEX (`absence_date`)
- INDEX (`status`)
- INDEX (`manager_status`, `hr_status`)

---

#### `permission_requests` Table
Permission/exit requests.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | User FK |
| `departure_time` | TIMESTAMP | Exit time |
| `return_time` | TIMESTAMP | Return time |
| `minutes_used` | INTEGER | Duration (minutes) |
| `remaining_minutes` | INTEGER | Balance left |
| `reason` | TEXT | Permission reason |
| `status` | ENUM | pending, approved, rejected |
| `manager_status` | ENUM | pending, approved, rejected |
| `hr_status` | ENUM | pending, approved, rejected |
| `returned_at` | TIMESTAMP | Actual return |
| `return_status` | ENUM | not_returned, on_time, late |
| `created_at` | TIMESTAMP | Request time |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`user_id`)
- INDEX (`departure_time`)
- INDEX (`status`)

---

#### `over_time_requests` Table
Overtime requests.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | User FK |
| `overtime_date` | DATE | OT date |
| `start_time` | TIME | Start time |
| `end_time` | TIME | End time |
| `hours` | DECIMAL(4,2) | Total hours |
| `reason` | TEXT | OT reason |
| `status` | ENUM | pending, approved, rejected |
| `manager_status` | ENUM | pending, approved, rejected |
| `manager_rejection_reason` | TEXT | Manager notes |
| `hr_status` | ENUM | pending, approved, rejected |
| `hr_rejection_reason` | TEXT | HR notes |
| `created_at` | TIMESTAMP | Request time |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`user_id`)
- INDEX (`overtime_date`)
- INDEX (`status`)

---

### 5. CRM

#### `clients` Table
Client database.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(255) | Client name |
| `company` | VARCHAR(255) | Company name |
| `email` | VARCHAR(255) | Email address |
| `phone` | VARCHAR(20) | Phone number |
| `industry` | VARCHAR(100) | Industry type |
| `status` | ENUM | lead, active, inactive |
| `address` | TEXT | Full address |
| `website` | VARCHAR(255) | Website URL |
| `notes` | TEXT | Internal notes |
| `assigned_to` | BIGINT UNSIGNED | Account manager |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`email`)
- INDEX (`status`)
- INDEX (`assigned_to`)

---

#### `client_tickets` Table
Support tickets.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `ticket_number` | VARCHAR(50) | Unique ticket # |
| `client_id` | BIGINT UNSIGNED | Client FK |
| `title` | VARCHAR(255) | Ticket title |
| `description` | TEXT | Issue description |
| `priority` | ENUM | low, medium, high, urgent |
| `status` | ENUM | open, in_progress, on_hold, resolved, closed |
| `department` | ENUM | technical, billing, general |
| `created_by` | BIGINT UNSIGNED | Creator |
| `resolved_at` | TIMESTAMP | Resolution time |
| `resolved_by` | BIGINT UNSIGNED | Resolver |
| `resolution_notes` | TEXT | Resolution |
| `created_at` | TIMESTAMP | Creation |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`ticket_number`)
- INDEX (`client_id`)
- INDEX (`status`, `priority`)
- INDEX (`created_by`)

---

### 6. Gamification

#### `seasons` Table
Competition seasons.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(255) | Season name |
| `start_date` | DATE | Season start |
| `end_date` | DATE | Season end |
| `is_active` | BOOLEAN | Active status |
| `description` | TEXT | Description |
| `created_at` | TIMESTAMP | Creation |
| `updated_at` | TIMESTAMP | Last update |

---

#### `badges` Table
Achievement badges.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `season_id` | BIGINT UNSIGNED | Season FK |
| `name` | VARCHAR(255) | Badge name |
| `icon` | VARCHAR(255) | Icon path |
| `color` | VARCHAR(50) | Badge color |
| `min_points` | INTEGER | Min points needed |
| `max_points` | INTEGER | Max points limit |
| `rank_order` | INTEGER | Rank position |
| `description` | TEXT | Description |
| `created_at` | TIMESTAMP | Creation |
| `updated_at` | TIMESTAMP | Last update |

---

#### `user_points` Table
User points tracking.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | User FK |
| `season_id` | BIGINT UNSIGNED | Season FK |
| `total_points` | INTEGER | Total points |
| `tasks_points` | INTEGER | From tasks |
| `additional_tasks_points` | INTEGER | From bonuses |
| `transfer_points` | INTEGER | From transfers |
| `revision_deductions` | INTEGER | Lost points |
| `current_badge_id` | BIGINT UNSIGNED | Current badge |
| `rank` | INTEGER | Season rank |
| `created_at` | TIMESTAMP | Creation |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`user_id`, `season_id`)
- INDEX (`season_id`, `total_points`)

---

## 🔗 Key Relationships

### User Relationships
```
User
├── has many → Attendances
├── has many → AbsenceRequests
├── has many → PermissionRequests
├── has many → OvertimeRequests
├── has many → TaskUsers (assigned tasks)
├── has many → ProjectServiceUsers (project participations)
├── has many → UserBadges
├── has many → UserPoints
├── has many → ClientTickets (created)
├── belongs to many → Teams
├── belongs to many → Roles
└── belongs to many → Permissions
```

### Project Relationships
```
Project
├── belongs to → Client
├── has many → ProjectServiceUsers (participants)
├── has many → ProjectTasks
├── has many → ProjectAttachments
├── has many → ProjectRevisions
├── has many → ProjectNotes
└── has many through → Users (via ProjectServiceUser)
```

### Task Relationships
```
Task
├── belongs to → Project (nullable)
├── belongs to → Service (nullable)
├── belongs to → GraphicTaskType
├── has many → TaskUsers (assignments)
├── has many → TaskRevisions
├── has many → TaskItems (checklist)
└── belongs to → User (creator)
```

---

## 📈 Indexing Strategy

### Primary Indexes
- All tables have auto-incrementing primary keys
- Unique constraints on natural keys (email, code, token, etc.)

### Foreign Key Indexes
- All foreign key columns are indexed
- Composite indexes for common query patterns

### Performance Indexes
- Status fields (for filtering)
- Date fields (for range queries)
- Timestamp fields (for sorting)

### Full-Text Indexes
- On description and notes fields (where applicable)

---

## 🔒 Data Integrity

### Constraints
- Foreign key constraints with CASCADE or RESTRICT
- NOT NULL constraints on required fields
- CHECK constraints on ENUM values
- UNIQUE constraints on natural keys

### Soft Deletes
- Implemented on critical tables: projects, tasks, attachments
- Uses `deleted_at` timestamp column
- Allows data recovery

### Timestamps
- All tables have `created_at` and `updated_at`
- Automatically managed by Laravel
- Timezone-aware (UTC storage)

---

## 💾 Storage Optimization

### Text Fields
- TEXT for long descriptions
- VARCHAR for limited strings
- JSON for structured data (emergency contacts, settings)

### Numeric Fields
- BIGINT for IDs (supports large datasets)
- INTEGER for counts
- DECIMAL for precise calculations (money, hours)

### Date/Time Fields
- DATE for calendar dates
- TIME for time-of-day
- TIMESTAMP for exact moments
- Uses timezone-aware storage

---

## 🔐 Security Considerations

### Sensitive Data
- Passwords: Hashed using bcrypt
- 2FA secrets: Encrypted at rest
- FCM tokens: Stored securely
- Personal data: Encrypted where required

### Audit Trail
- `activity_log` table tracks all changes
- Stores old and new values
- Tracks user who made change

### Data Access
- Row-level security via policies
- Soft deletes for data retention
- Regular backups

---

## 📊 Performance Considerations

### Query Optimization
- Eager loading relationships
- Index on frequently queried columns
- Denormalization where appropriate (completion_percentage, total_points)

### Partitioning (Future)
- Consider partitioning large tables by date
- Attendance table by year/month
- Activity log by month

### Caching Strategy
- Cache frequently accessed data
- Cache computed values
- Invalidate on updates

---

**Schema Version**: 1.0  
**Last Updated**: 2024  
**Database Engine**: MySQL 5.7+ / MariaDB 10.3+  
**Charset**: utf8mb4_unicode_ci

