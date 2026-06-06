# Phase 1 — Build Plan: IGXTelle Law Group Task Management App

## Vertical Slices (Build Order)

| # | Slice | What it delivers |
|---|-------|-----------------|
| 0 | Foundation | Packages, migrations, models, enums, roles seeder, middleware shared data, route skeleton |
| 1 | Projects CRUD | Project model → policy → form requests → resource → controller → Vue pages → tests |
| 2 | Tasks CRUD | Task model/enums → policy → form requests → resource → controller → Vue pages → tests |
| 3 | Kanban Board | `TaskStatusController`, drag-and-drop UI (`vuedraggable`), optimistic update + rollback, tests |
| 4 | Users & Team | `UserController`, `InvitationController`, full invitation flow, role assignment, deactivate/remove, resend/revoke, tests |
| 5 | Dashboard | Stats aggregation, `DashboardController`, updated Dashboard Vue page, tests |
| 6 | Notifications | DB notifications, `NotificationController`, shared unread count, notification list page, tests |

---

## Slice 0 — Foundation

### Packages to install
- PHP: `spatie/laravel-permission`
- JS: `vuedraggable` (`vuedraggable@4`, Vue 3 compatible)

### Migrations (new)
1. **Alter users table** — drop `id` bigInteger PK, add `uuid('id')->primary()` (UUIDv7 via `HasUuids`); also add `deleted_at (timestamp, nullable)` for `SoftDeletes`. Update dependent tables: change `passkeys.user_id` and `sessions.user_id` from `foreignId` to `uuid` FK.
2. **Spatie Permission tables** — via `vendor:publish`. Config (`config/permission.php`) must set `column_names.model_morph_key = 'model_uuid'` so Spatie stores UUID model IDs in `model_has_roles` and `model_has_permissions`.
3. **`create_projects_table`** — `uuid('id')->primary()`, `title (string)`, `description (text, nullable)`, `lead_id (uuid FK → users)`, `status (string, default ACTIVE)`, `timestamps()`
4. **`create_tasks_table`** — `uuid('id')->primary()`, `project_id (uuid FK → projects)`, `title (string)`, `description (text, nullable)`, `assigned_to (uuid FK → users, nullable)`, `status (string, default TODO)`, `priority (string, default MEDIUM)`, `due_date (date, nullable)`, `completed_at (timestamp, nullable)`, `timestamps()`
5. **`create_invitations_table`** — `uuid('id')->primary()`, `email (string)`, `role (string)`, `token (string 64, nullable)`, `invited_by (uuid FK → users)`, `expires_at (timestamp)`, `accepted_at (timestamp, nullable)`, `revoked_at (timestamp, nullable)`, `timestamps()`
6. **`notifications` table** — via `php artisan notifications:table`

> **UUIDs everywhere**: All models — including `User` — use `HasUuids` + `uuid('id')->primary()`. All foreign keys referencing `users` use `uuid` columns (not `foreignId()`). Spatie Permission is configured to store UUID morph keys via `model_morph_key = 'model_uuid'`.

### Enums
```
app/Enums/
├── ProjectStatus.php   (ACTIVE, ON_HOLD, COMPLETED, ARCHIVED)
├── TaskStatus.php      (TODO, IN_PROGRESS, REVIEW, DONE)
└── TaskPriority.php    (LOW, MEDIUM, HIGH, URGENT)
```
Each backed by `string`, each case returns `label(): string`.

### Models (create/update)
- `User` — add `HasUuids`, `HasRoles` (Spatie), `SoftDeletes`; keep all existing Fortify traits; switch `#[Fillable]` attribute to `#[Guarded(['id'])]`
- `Project` — `HasUuids`, `HasFactory`, `#[Guarded(['id'])]`; relationships: `lead()`, `tasks()`
- `Task` — `HasUuids`, `HasFactory`, `#[Guarded(['id'])]`; enums cast, `overdue` scope, `isOverdue()` helper; relationships: `project()`, `assignee()`
- `Invitation` — `HasUuids`, `HasFactory`, `#[Guarded(['id'])]`; `isExpired()`, `isValid()`, `isRevoked()`; relationship: `inviter()`

### Seeders
- `RoleSeeder` — creates 4 roles: `Super Admin`, `Admin`, `Operations Manager`, `Staff`
- `DatabaseSeeder` calls `RoleSeeder`

### Route Structure
Per the coding style skill's segmented pattern:
```
routes/
├── web.php                             ← requires definitions aggregate
└── definitions/web/
    ├── web-routes.php                  ← requires all sections
    └── sections/
        ├── dashboard-routes.php
        ├── project-routes.php
        ├── task-routes.php
        ├── team-routes.php
        ├── notification-routes.php
        └── invitation-routes.php       ← public (no auth required)
```

### `HandleInertiaRequests` — shared data
```php
'auth' => [
    'user' => $request->user()?->load('roles'),
],
'flash' => [
    'success' => session('success'),
    'error'   => session('error'),
],
'notifications' => [
    'unreadCount' => $request->user()?->unreadNotifications()->count() ?? 0,
],
```

---

## Slice 1 — Projects CRUD

| Layer | File |
|-------|------|
| Migration | `create_projects_table` (in Slice 0) |
| Model | `app/Models/Project.php` |
| Policy | `app/Policies/ProjectPolicy.php` |
| Form Requests | `app/Http/Requests/Project/StoreProjectRequest.php`, `UpdateProjectRequest.php` |
| Resource | `app/Http/Resources/Project/ProjectResource.php` |
| Controller | `app/Http/Controllers/Project/ProjectController.php` (full resource) |
| Routes | `routes/definitions/web/sections/project-routes.php` |
| Vue Pages | `pages/projects/Index.vue`, `Show.vue`, `Create.vue`, `Edit.vue` |
| Types | `Project` interface in `resources/js/types/index.ts` |
| Tests | `tests/Feature/Projects/ProjectControllerTest.php` |

**Project permissions:**

| Action | Super Admin | Admin | Ops Manager | Staff |
|--------|:-----------:|:-----:|:-----------:|:-----:|
| viewAny / view | ✓ | ✓ | ✓ | ✓ |
| create | ✓ | ✓ | ✓ | ✗ |
| update | ✓ | ✓ | ✓ | ✗ |
| delete | ✓ | ✓ | ✗ | ✗ |

---

## Slice 2 — Tasks CRUD

| Layer | File |
|-------|------|
| Enums | `TaskStatus`, `TaskPriority` (Slice 0) |
| Model | `app/Models/Task.php` |
| Policy | `app/Policies/TaskPolicy.php` |
| Form Requests | `StoreTaskRequest`, `UpdateTaskRequest` |
| Resource | `app/Http/Resources/Task/TaskResource.php` |
| Controller | `app/Http/Controllers/Task/TaskController.php` (shallow nested under project) |
| Routes | `routes/definitions/web/sections/task-routes.php` |
| Vue Pages | `pages/tasks/Index.vue`, `Create.vue`, `Edit.vue` |
| Types | `Task`, `TaskStatus`, `TaskPriority` interfaces |
| Tests | `tests/Feature/Tasks/TaskControllerTest.php` |

**Task permissions:**

| Action | Super Admin | Admin | Ops Manager | Staff |
|--------|:-----------:|:-----:|:-----------:|:-----:|
| viewAny / view | ✓ | ✓ | ✓ | ✓ |
| create | ✓ | ✓ | ✓ | ✗ |
| update (full) | ✓ | ✓ | ✓ | ✗ |
| updateStatus | ✓ | ✓ | ✓ | ✓ (own assigned only) |
| delete | ✓ | ✓ | ✓ | ✗ |
| assign user | ✓ | ✓ | ✓ | ✗ |

---

## Slice 3 — Kanban Board

| Layer | File |
|-------|------|
| Controller | `app/Http/Controllers/Task/TaskStatusController.php` |
| Route | `PATCH projects/{project}/tasks/{task}/status` → `tasks.status.update` |
| Vue Components | `components/task/KanbanBoard.vue`, `KanbanColumn.vue`, `KanbanCard.vue` |
| Tests | `tests/Feature/Tasks/TaskStatusControllerTest.php` |

The Kanban board renders on `projects/Show.vue`. Each column maps to a `TaskStatus` value. Drag-and-drop fires a `PATCH` via `useHttp` (Inertia v3) with an optimistic state update; on HTTP failure the card snaps back to its original column. `TaskStatusController::update` returns JSON (200) so the XHR path stays lightweight.

---

## Slice 4 — Users & Team Management

| Layer | File |
|-------|------|
| Migration | `create_invitations_table`, alter users `deleted_at` (Slice 0) |
| Models | `Invitation`, update `User` |
| Policies | `app/Policies/UserPolicy.php`, `InvitationPolicy.php` |
| Form Requests | `StoreInvitationRequest`, `UpdateUserRoleRequest` |
| Resources | `app/Http/Resources/User/UserResource.php` |
| Action | `app/Actions/Invitation/AcceptInvitationAction.php` |
| Mailable | `app/Mail/InvitationMail.php` |
| Controllers | `app/Http/Controllers/Team/UserController.php`, `InvitationController.php`, `InvitationAcceptController.php` |
| Routes | `team-routes.php` (auth), `invitation-routes.php` (public) |
| Vue Pages | `pages/team/Index.vue`, `pages/invitations/Accept.vue` |
| Tests | `tests/Feature/Team/UserControllerTest.php`, `InvitationControllerTest.php`, `InvitationFlowTest.php` |
| Unit Tests | `tests/Unit/InvitationTokenTest.php`, `tests/Unit/RoleEscalationTest.php` |

### Invitation flow

```
1. POST /team/invitations
   ├── StoreInvitationRequest: validate email + role (escalation guard runs here)
   ├── $plain = bin2hex(random_bytes(32))          // 64-char hex
   ├── DB stores: token = hash('sha256', $plain)
   ├── expires_at = now() + 72 hours
   └── Queue: InvitationMail($plain) → invitee email

2. GET /invitations/accept?token={plain}  (public, no auth)
   ├── Find: where token = hash('sha256', plain)
   ├── Validate: exists && !expired && !accepted && !revoked
   ├── Valid   → render Accept.vue with { email, role, token: plain }
   └── Invalid → render Accept.vue with { invalid: true }

3. POST /invitations/accept  (public)
   ├── Re-validate token (idempotency safety)
   ├── AcceptInvitationAction:
   │   ├── User::create({ name, email, password: bcrypt($pw) })
   │   ├── $user->assignRole($invitation->role)
   │   ├── $invitation->update({ accepted_at: now(), token: null })
   │   └── Auth::login($user)
   └── Redirect → /dashboard  (flash: "Welcome! Configure 2FA/passkeys in Settings.")

4. DELETE /team/invitations/{invitation}  (revoke)
   └── $invitation->update({ revoked_at: now(), token: null })

5. POST /team/invitations/{invitation}/resend
   ├── New $plain token, new expires_at = now() + 72h
   └── Queue: InvitationMail (new plain token)
```

### Role-escalation guard

Roles are assigned a numeric rank: `Staff = 1`, `Operations Manager = 2`, `Admin = 3`, `Super Admin = 4`.

**Rule:** An acting user may only assign a role whose rank ≤ their own highest role rank.
- Super Admin (4): can assign any role
- Admin (3): can assign Staff, Operations Manager, Admin — **not** Super Admin
- Operations Manager (2): can assign Staff, Operations Manager only
- Staff: cannot assign any role

Enforced in: `StoreInvitationRequest::authorize()`, `UpdateUserRoleRequest::authorize()`, and mirrored in `InvitationPolicy` + `UserPolicy`.

**Team permissions:**

| Action | Super Admin | Admin | Ops Manager | Staff |
|--------|:-----------:|:-----:|:-----------:|:-----:|
| View team list | ✓ | ✓ | ✓ | ✗ |
| Invite user | ✓ | ✓ | ✓ (rank ≤ 2) | ✗ |
| Assign / change role | ✓ | ✓ | ✓ (rank ≤ 2) | ✗ |
| Deactivate user (soft delete) | ✓ | ✓ | ✗ | ✗ |
| Permanently delete user | ✓ | ✗ | ✗ | ✗ |
| Revoke invitation | ✓ | ✓ | ✓ (own only) | ✗ |
| Resend invitation | ✓ | ✓ | ✓ (own only) | ✗ |

---

## Slice 5 — Dashboard

| Layer | File |
|-------|------|
| Controller | `app/Http/Controllers/DashboardController.php` |
| Vue Page | `pages/Dashboard.vue` (update existing) |
| Tests | `tests/Feature/DashboardTest.php` (update existing) |

**Stats rendered:**
- Open tasks count (status ≠ DONE)
- Overdue tasks count
- Projects count by status
- Active team members count
- My assigned open tasks (for the authenticated user)

---

## Slice 6 — Notifications

| Layer | File |
|-------|------|
| Notification | `app/Notifications/TaskAssignedNotification.php` |
| Controller | `app/Http/Controllers/Notification/NotificationController.php` (index, markAsRead, markAllAsRead) |
| Routes | `notification-routes.php` |
| Vue Page | `pages/notifications/Index.vue` |
| Tests | `tests/Feature/Notifications/NotificationControllerTest.php` |

**Triggers:**
- `TaskAssignedNotification` → DB notification → sent to `assigned_to` user when task is created/updated with a new assignee
- Invitation email → `InvitationMail` (queue) — email only, no DB notification (invitee is not yet a user)

Unread count is shared via `HandleInertiaRequests` (set up in Slice 0).

---

## Full Role → Permission Matrix

| Action | Super Admin | Admin | Ops Manager | Staff |
|--------|:-----------:|:-----:|:-----------:|:-----:|
| **Projects** | | | | |
| View all / single project | ✓ | ✓ | ✓ | ✓ |
| Create project | ✓ | ✓ | ✓ | ✗ |
| Edit project | ✓ | ✓ | ✓ | ✗ |
| Delete project | ✓ | ✓ | ✗ | ✗ |
| **Tasks** | | | | |
| View all tasks | ✓ | ✓ | ✓ | ✓ |
| Create task | ✓ | ✓ | ✓ | ✗ |
| Edit task (full — title, priority, due date, assignee) | ✓ | ✓ | ✓ | ✗ |
| Update task status (drag-and-drop) | ✓ | ✓ | ✓ | ✓ (own assigned only) |
| Delete task | ✓ | ✓ | ✓ | ✗ |
| **Team** | | | | |
| View team list | ✓ | ✓ | ✓ | ✗ |
| Invite user | ✓ | ✓ | ✓ (rank ≤ 2) | ✗ |
| Assign / change role | ✓ | ✓ | ✓ (rank ≤ 2) | ✗ |
| Deactivate user (soft delete) | ✓ | ✓ | ✗ | ✗ |
| Permanently delete user | ✓ | ✗ | ✗ | ✗ |
| Revoke invitation | ✓ | ✓ | ✓ (own only) | ✗ |
| Resend invitation | ✓ | ✓ | ✓ (own only) | ✗ |
| **Notifications** | | | | |
| View / mark as read | ✓ | ✓ | ✓ | ✓ |

---

## Ambiguities & Assumed Defaults

| # | Ambiguity | Assumed Default |
|---|-----------|-----------------|
| 1 | Users table has bigInteger PK; prompt says UUIDs everywhere | **Migrate `users` to UUIDv7.** Add a migration that drops the bigInteger PK, adds `uuid('id')->primary()`, and updates `passkeys.user_id` and `sessions.user_id` to uuid FKs. Spatie Permission configured with `model_morph_key = 'model_uuid'`. All FKs to `users` use `uuid` columns. |
| 2 | Staff project/task visibility | Staff sees ALL projects and tasks (read). Can only update status of tasks assigned to them. |
| 3 | Ops Manager invitation scope | Can only revoke/resend invitations where `invited_by = auth()->id()`. |
| 4 | "You've been invited" notification | Email-only (`InvitationMail` queued). No DB notification — invitee is not yet a user. |
| 5 | ProjectStatus values | `ACTIVE`, `ON_HOLD`, `COMPLETED`, `ARCHIVED`; default `ACTIVE`. |
| 6 | Passkey on acceptance | No forced passkey step. After login, redirect to `/dashboard` with flash: *"Welcome! Visit Settings → Security to set up 2FA or a passkey."* |
| 7 | Admin escalation guard | Admin (rank 3) cannot assign Super Admin (rank 4). Only Super Admin can assign Super Admin. |
| 8 | `TaskAssignedNotification` trigger | Fires when `assigned_to` is set or changed on create/update, to the newly assigned user only. |
| 9 | `vuedraggable` package | `vuedraggable@4` (Vue 3, wraps SortableJS). |
| 10 | Kanban endpoint response type | JSON (not Inertia redirect) so the drag-and-drop XHR path is lightweight and rollback is clean. |
