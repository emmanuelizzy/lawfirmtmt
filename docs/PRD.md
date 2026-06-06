# Product Requirements Document (PRD)
## IGXTelle Law Group — Internal Team & Task Management Tool

**Document version:** 1.0
**Status:** Draft (Phase 1 — MVP)
**Author:** [Your Name]
**Last updated:** June 2026
**Phase:** 1 of 3 (MVP / Demo & First Deliverable)

---

## 1. Overview

### 1.1 Purpose
IGXTelle Law Group needs an in-house web application that allows the entire team to log in, manage tasks across the different legal works ("matters") the firm handles, track team workload, receive notifications when tasks become overdue, and view a dashboard of the firm's overall workflow.

This document defines the scope, requirements, data model, and deliverables for **Phase 1 (MVP)** — the version that will be demoed to the client and deployed as the first paid milestone.

### 1.2 Background
The client described the need as:

> "We want to build an in-house tool that everyone is able to login and have different task on every work we are doing in igxtelle law group. I wanted to have the tool to manage the team properly, we get notification when the task is overdue, where we can see the dashboard of how our workflow is within the law firm."

This is an **ongoing engagement**. Phase 1 delivers a genuinely usable product (not a prototype) while keeping scope tight enough to ship quickly and unlock the first invoice.

### 1.3 Goals
- Provide secure, role-based login for all team members.
- Allow work to be organised under **Matters** (legal cases/projects).
- Allow tasks to be created, assigned, prioritised, and tracked under each matter.
- Notify assignees (in-app + email) when tasks become overdue.
- Provide a dashboard summarising firm-wide workflow and individual workload.

### 1.4 Non-Goals (explicitly out of scope for Phase 1)
- Time tracking / billing / invoicing.
- Document storage and management.
- Client-facing portal.
- WhatsApp / SMS notifications.
- Calendar view and recurring tasks.
- Audit logging and data export/reporting.
- Migration/import from existing tools.

These are noted for Phase 2 / Phase 3 so the architecture can accommodate them, but they are **not built** in Phase 1.

---

## 2. Tech Stack

| Layer | Technology | Notes |
|---|---|---|
| Backend framework | Laravel 13 | API + server-side logic |
| Frontend | Vue 3 (Composition API) | SPA-like UX |
| Bridge | Inertia.js | No separate REST API needed in Phase 1 |
| Database | **PostgreSQL** | Chosen for richer aggregation/analytics, JSONB, stronger data integrity, native full-text search |
| Auth scaffolding | Laravel Breeze (Inertia + Vue preset) | Login, password reset, profile |
| Roles & permissions | `spatie/laravel-permission` | Role hierarchy |
| Queue & cache | Redis | Powers notifications + scheduled jobs |
| Styling | Tailwind CSS | Ships with Breeze |
| Hosting | DigitalOcean droplet | Provisioned via Forge |
| Deployment | Laravel Forge | Quick deploy, scheduler, queue daemon, SSL |

### 2.1 Why PostgreSQL over MySQL
- Cleaner analytics queries for the dashboard (window functions, `FILTER` clauses, richer aggregation).
- JSONB support for future flexible metadata (custom fields, notification payloads).
- Stronger data integrity (strict typing, check constraints) — important for a legal client.
- Solid native full-text search for when matters/tasks accumulate.
- No MySQL advantage applies here since hosting is managed via Forge (not shared hosting).

---

## 3. User Roles & Permissions

Three roles in Phase 1. Final hierarchy to be confirmed with the client during the meeting.

| Role | Description | Key permissions |
|---|---|---|
| **Admin / Partner** | Firm owner / senior partner | Full access: manage users, matters, all tasks, view full dashboard |
| **Manager** | Team lead / supervising associate | Create/manage matters, assign tasks to staff, view team dashboard |
| **Staff** | Associate / paralegal / admin staff | View assigned matters, manage own tasks, update task status |

### 3.1 Permission matrix

| Action | Admin | Manager | Staff |
|---|:---:|:---:|:---:|
| Manage users (CRUD) | ✅ | ❌ | ❌ |
| Assign roles | ✅ | ❌ | ❌ |
| Create / edit / delete matters | ✅ | ✅ | ❌ |
| View all matters | ✅ | ✅ | Assigned only |
| Create tasks | ✅ | ✅ | ✅ (own) |
| Assign tasks to others | ✅ | ✅ | ❌ |
| Update task status | ✅ | ✅ | ✅ (assigned) |
| Delete tasks | ✅ | ✅ | ❌ |
| View firm-wide dashboard | ✅ | ✅ | Personal only |

---

## 4. Functional Requirements

### 4.1 Authentication & Profile
- **FR-1.1** Users can log in with email and password.
- **FR-1.2** Users can reset a forgotten password via email.
- **FR-1.3** Users can update their own name, email, and password.
- **FR-1.4** Admin can create user accounts and assign a role on creation.
- **FR-1.5** Sessions expire after a configurable period of inactivity.

### 4.2 User Management (Admin)
- **FR-2.1** Admin can view a list of all users with their roles.
- **FR-2.2** Admin can create, edit, deactivate, and delete users.
- **FR-2.3** Admin can change a user's role.
- **FR-2.4** Deactivated users cannot log in but their historical task data is retained.

### 4.3 Matters (Cases / Projects)
- **FR-3.1** Admin/Manager can create a matter with: name, client name, description, lead (assigned user), and status.
- **FR-3.2** Matter statuses: `Open`, `In Progress`, `On Hold`, `Closed`.
- **FR-3.3** Users can view a list of matters (filtered by their permissions).
- **FR-3.4** Each matter has a detail page showing its tasks, lead, status, and progress.
- **FR-3.5** Admin/Manager can edit and delete (soft delete) matters.
- **FR-3.6** Matter detail page shows a progress indicator (% tasks completed).

### 4.4 Tasks
- **FR-4.1** Users can create tasks with: title, description, parent matter, assignee, priority, due date, status.
- **FR-4.2** Task statuses: `To Do`, `In Progress`, `Review`, `Done`.
- **FR-4.3** Task priorities: `Low`, `Medium`, `High`, `Urgent`.
- **FR-4.4** Tasks can be filtered by status, assignee, priority, matter, and due date.
- **FR-4.5** A task marked `Done` records a `completed_at` timestamp.
- **FR-4.6** Overdue tasks (past due date, not Done) are visually flagged (e.g. red badge).
- **FR-4.7** Users can view "My Tasks" — all tasks assigned to them across matters.

### 4.5 Kanban Board
- **FR-5.1** Tasks for a matter (or globally) are displayed in a Kanban board with columns matching task statuses.
- **FR-5.2** Users can drag and drop tasks between columns to change status.
- **FR-5.3** Status changes persist immediately to the backend.
- **FR-5.4** Each card shows title, assignee avatar/initials, priority, and due date (with overdue highlight).

### 4.6 Notifications (Overdue Tasks)
- **FR-6.1** A scheduled job runs hourly to detect tasks that are overdue and not Done.
- **FR-6.2** The assignee receives an **in-app notification** (bell icon with unread count).
- **FR-6.3** The assignee receives an **email notification**.
- **FR-6.4** The matter lead is optionally notified for overdue tasks within their matter.
- **FR-6.5** Notifications are not duplicated for the same task within a single overdue window.
- **FR-6.6** Users can mark notifications as read and view notification history.

### 4.7 Dashboard
- **FR-7.1** Display summary cards: total tasks, open tasks, overdue tasks, completed (this week).
- **FR-7.2** Display tasks-by-status breakdown (chart).
- **FR-7.3** Display workload per team member (tasks assigned, count by status).
- **FR-7.4** Display a completion trend over time (e.g. last 7/30 days).
- **FR-7.5** Display a list of upcoming/overdue tasks.
- **FR-7.6** Staff see a personal dashboard scoped to their own tasks; Admin/Manager see firm-wide data.

---

## 5. Data Model

### 5.1 Entity overview

```
users          → tasks (assigned_to), matters (lead)
matters        → tasks (one-to-many)
tasks          → comments (phase 2), attachments (phase 2)
notifications  → users (Laravel built-in)
```

### 5.2 Tables

**users**
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| email | varchar | unique |
| password | varchar | hashed |
| role | enum/string | via spatie |
| is_active | boolean | default true |
| timestamps | | |

**matters**
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| client_name | varchar | |
| description | text | nullable |
| lead_user_id | bigint FK → users | nullable |
| status | enum | Open / In Progress / On Hold / Closed |
| created_by | bigint FK → users | |
| deleted_at | timestamp | soft delete |
| timestamps | | |

**tasks**
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| title | varchar | |
| description | text | nullable |
| matter_id | bigint FK → matters | |
| assigned_to | bigint FK → users | nullable |
| created_by | bigint FK → users | |
| status | enum | To Do / In Progress / Review / Done |
| priority | enum | Low / Medium / High / Urgent |
| due_date | date | nullable |
| completed_at | timestamp | nullable |
| deleted_at | timestamp | soft delete |
| timestamps | | |

**notifications** — Laravel's default `notifications` table (database channel).

### 5.3 Indexing
Add indexes early on:
- `tasks.assigned_to`
- `tasks.due_date`
- `tasks.status`
- `tasks.matter_id`
- `matters.lead_user_id`

These power the dashboard aggregation and overdue detection queries.

### 5.4 Example migration snippet

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('created_by')->constrained('users');
    $table->enum('status', ['todo', 'in_progress', 'review', 'done'])->default('todo');
    $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
    $table->date('due_date')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->softDeletes();
    $table->timestamps();

    $table->index(['assigned_to', 'status', 'due_date']);
});
```

---

## 6. Notifications Architecture

```
Scheduler (cron via Forge) ──► tasks:check-overdue command
        │                              │
        │                              ▼
        │              Query overdue, not-done tasks
        │                              │
        │                              ▼
        │              Dispatch OverdueTaskNotification
        │                              │
        ▼                              ▼
   Redis Queue ──► Queue Worker ──► [ database channel ] → in-app bell
                                  └► [ mail channel ]     → email
```

- A custom Artisan command `tasks:check-overdue` is scheduled hourly.
- It selects tasks where `due_date < now()` AND `status != 'done'`.
- A guard prevents duplicate notifications within the same overdue window (e.g. a `last_overdue_notified_at` column or cache check).
- Notifications dispatched via queue to keep the scheduler fast.

### 6.1 Required Forge configuration
- **Scheduler:** enable Forge's one-click cron running `php artisan schedule:run` every minute.
- **Queue worker:** create a Forge daemon running `php artisan queue:work redis`.

---

## 7. UI / Screens (Phase 1)

| Screen | Description |
|---|---|
| Login / Password reset | Breeze default, lightly branded |
| Dashboard | Summary cards, charts, workload, overdue list |
| Matters list | Table of matters with status, lead, progress |
| Matter detail | Matter info + tasks (list + Kanban toggle) |
| Kanban board | Drag-and-drop task columns |
| My Tasks | Personal task list across all matters |
| Task create/edit | Modal or page with all task fields |
| Users (Admin) | User CRUD + role assignment |
| Notifications | Bell dropdown + full notifications page |
| Profile | Edit name/email/password |

### 7.1 Branding note
Use **law-firm terminology** throughout — "Matters" not "Projects" — so the demo feels purpose-built for IGXTelle rather than a generic clone. (Confirm preferred term — Matters / Cases / Projects — with client.)

---

## 8. Deployment

### 8.1 Infrastructure
- **DigitalOcean droplet** provisioned through Forge (start with a $12–24/mo box).
- **PostgreSQL** on the same droplet for Phase 1 (split out later if needed).
- **Redis** for queue + cache.
- **Let's Encrypt** SSL via Forge (free, auto-renew).

### 8.2 Environments
- **Staging** (`staging.[domain]`) — for demos and iteration.
- **Production** (`app.[domain]`) — live client environment.

### 8.3 CI/CD
- Connect Git repo to Forge.
- Enable **quick deploy** on push to `main` (production) and a `staging` branch.
- Deploy script runs: `composer install`, `npm ci && npm run build`, `php artisan migrate --force`, cache clears.

### 8.4 Deployment checklist
- [ ] Droplet provisioned
- [ ] PostgreSQL database created + credentials in `.env`
- [ ] Redis installed
- [ ] Scheduler cron enabled
- [ ] Queue worker daemon running
- [ ] Mail driver configured (for notification emails)
- [ ] SSL issued
- [ ] Seeder run on staging with demo law-firm data

---

## 9. Demo Plan (Meeting Deliverable)

To close the deal, walk in with a **deployed staging URL** showing real-looking seeded data, not slides:

1. Log in as Admin → show populated dashboard.
2. Open a matter → show its tasks on the Kanban board.
3. Drag a task between columns → status updates live.
4. Create a new task with a past due date → show it flagged overdue.
5. Show the bell notification + sample overdue email.
6. Log in as Staff → show scoped view + personal dashboard.

### 9.1 Seeder
Seed realistic data: ~5 users (across roles), ~6 matters with client names, ~30 tasks across all statuses/priorities, several intentionally overdue.

---

## 10. Acceptance Criteria (Phase 1 sign-off)

Phase 1 is considered complete when:
- [ ] All roles can log in and see permission-appropriate views.
- [ ] Admin can manage users and assign roles.
- [ ] Matters can be created, viewed, edited, soft-deleted.
- [ ] Tasks can be created, assigned, and moved across Kanban columns.
- [ ] Overdue tasks trigger in-app + email notifications via the scheduler.
- [ ] Dashboard displays accurate summary cards, charts, and workload.
- [ ] Application is deployed to production on Forge with SSL.
- [ ] Scheduler and queue worker are running and verified.

---

## 11. Open Questions (to confirm with client)

1. **Role hierarchy** — exact roles (Partners, associates, paralegals, admin)? Who can assign tasks to whom?
2. **Terminology** — "Matters", "Cases", or "Projects"?
3. **Notification channels** — is in-app + email sufficient, or is WhatsApp/SMS required (Phase 2, paid integration)?
4. **Scale** — how many users total? (affects droplet sizing)
5. **Future scope** — time tracking, billing, document storage, client portal expected later?
6. **Existing tools** — migrating from spreadsheets / Trello / Clio? Data import needed?

---

## 12. Roadmap (context beyond Phase 1)

| Phase | Scope |
|---|---|
| **Phase 1 (this doc)** | Auth, roles, matters, tasks, Kanban, overdue notifications, dashboard, deployment |
| **Phase 2** | Comments/activity feed, file attachments, checklists, email digests, WhatsApp notifications, real-time updates (Reverb) |
| **Phase 3** | Time tracking, reporting/exports, client portal, calendar view, audit log |

### 12.1 Billing alignment
Invoicing is tied to phase deliverables. Phase 1 sign-off (Section 10) triggers the first milestone payment.

---

*End of Phase 1 PRD.*