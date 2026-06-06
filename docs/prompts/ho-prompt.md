# Build Prompt — IGXTelle Law Group Task Management App

You are building a task/project management web app for IGXTelle Law Group.

## Stack & Ground Rules
- **Laravel 13 + Inertia.js + Vue 3 (Composition API, TypeScript) + shadcn-vue** monolith.
- Auth is **already implemented** (Fortify: 2FA + passkeys). Do not rebuild auth.
- You **must** follow the `igxtelle-coding-style` skill for every file you touch — PHP,
  Vue, TS, config, routes. This is non-negotiable. Re-read it before each slice.
- This is **NOT** a JSON:API project. Controllers return `Inertia::render()`; writes
  redirect back with flash. Use Wayfinder for all frontend URLs (never `route()` or
  hardcoded strings).

## Domain
- **Project**: a legal project/case container. Has tasks, a lead user, status, timestamps.
- **Task**: belongs to a Project, has an assignee, status (Kanban), priority, due date.
- **User**: existing auth user; carries one or more roles (spatie). Can be invited.
- **Invitation**: a pending invite (email, role, token, expiry, accepted_at).
- **Enums**: `TaskStatus` (TODO, IN_PROGRESS, REVIEW, DONE), `TaskPriority`
  (LOW, MEDIUM, HIGH, URGENT). Both implement `label()` per the skill.
- UUIDv7 primary keys everywhere (`HasUuids`, `uuid('id')->primary()`).

## Roles & Authorization
Use `spatie/laravel-permission`. Seed these four roles:
- **Super Admin** — full access to everything.
- **Admin** — manage all projects, tasks, and users (except Super-Admin-only concerns).
- **Operations Manager** — create/manage projects and tasks, **invite staff, assign
  roles, and assign tasks via the UI**.
- **Staff** — work on assigned tasks, update status; limited create/manage.

Enforce with **Policies** (`ProjectPolicy`, `TaskPolicy`, `UserPolicy`,
`InvitationPolicy`) AND route middleware (`role:`/`can:`) AND `authorize()` in every Form
Request. Never rely on hidden UI alone. Guard rails on the team features specifically:
- Only Super Admin / Admin / Operations Manager may invite users or assign roles.
- An Operations Manager must **not** be able to grant a role higher than their own
  (no escalating someone to Admin or Super Admin). State your exact rule in the plan.

(Full per-permission matrix is yours to propose in the plan — keep it sensible and state
your assumptions.)

## Features to deliver
1. **Projects**: index (list), show, create, edit, delete.
2. **Tasks**: index, create, edit, delete, scoped to a project; assign an assignee.
3. **Kanban board** with drag-and-drop status updates (`vuedraggable`,
   `TaskStatusController`, optimistic update + rollback).
4. **Users & Team Management**:
   - **Invite a user by email via a real tokened email-acceptance flow** (see below).
   - List users with their roles and status (active / invited / pending).
   - Assign / change a user's role(s) via the UI (respecting the escalation guard above).
   - Deactivate / remove a user (soft, where appropriate).
   - Resend / revoke a pending invitation.
5. **Dashboard**: at-a-glance counts (open/overdue tasks, projects, team, etc.).
6. **Notifications**: unread count in shared Inertia data; basic list + mark-as-read.
   Includes "you've been invited" and "task assigned to you" notifications.

## Invitation flow (pin this — do not improvise)
- An authorized user creates an invitation: pick email + role. This creates an
  `Invitation` record with a **signed, single-use, expiring token** (hash the token in
  the DB; never store it in plaintext). Default expiry: **72 hours** (note if you change).
- A **notification/mailable** is sent to the invitee containing the tokened acceptance
  link (built via Wayfinder / signed URL).
- The invitee clicks the link → lands on a public **Accept Invitation** page (no auth
  required). The token is validated: must exist, be unexpired, unused, and match the
  email. On any failure show a clean "invalid or expired invitation" state.
- On acceptance the invitee **sets a password and may register a passkey** (reuse the
  existing Fortify 2FA/passkey machinery — do not rebuild it). A `User` is created (or
  the pending user activated), the invited **role is assigned**, `accepted_at` is stamped,
  and the token is invalidated (single-use).
- The invitee is then logged in / redirected to the dashboard.
- Revoking a pending invite invalidates its token immediately.

---

## How to work (autonomy: C → B)

### Phase 1 — PLAN FIRST (stop and wait for approval)
Before writing any code, produce a written plan covering:
- The vertical slices you'll build and their order.
- Per-slice: migrations, models, enums, policies, form requests, controllers,
  resources, routes (+ Wayfinder regen), Vue pages/components, and tests.
- The role → permission matrix, the full invitation flow, and the role-escalation guard.
- Anything ambiguous: list it with your proposed default. **Do not block on me for small
  ambiguities — pick a sensible default, note it, and proceed once the plan is approved.**

**Then stop and wait for my approval before coding.**

### Phase 2 — BUILD SLICE BY SLICE (checkpoint per slice)
After plan approval, implement **one vertical slice at a time**. At the end of each slice:
- Run `vendor/bin/pint --dirty --format agent`, then `npm run lint` and `npm run format`.
- Run the Pest suite; ensure it's green.
- **Pause and check in with me** with a short summary of what you built and what's next,
  before starting the following slice.

### Progress visibility
Maintain a visible todo list throughout — use your native todo tool **or** a `TODO.md`
file at the repo root (your choice; I'm indifferent, I just need to see live status).
Keep it updated as you complete items.

### shadcn-vue
Before hand-rolling ANY UI primitive (button, dialog, table, card, badge, select,
dropdown, etc.), add it via the **shadcn-vue MCP**. Compose pages from primitives.

### Wayfinder
Frontend URLs come only from `@/routes/...` (named, preferred for links) or
`@/actions/...` (controller-bound, for forms). Never `route()` or string URLs.
Don't hand-edit or commit `resources/js/{wayfinder,actions,routes}`.

---

## Testing (required)
Write **Pest** tests as part of each slice (not batched at the end):
- **Feature tests** for every controller action — auth/role gating, validation,
  redirects + flash, correct Inertia component & props, the drag-and-drop status
  endpoint, and the role-escalation guard (assert an Operations Manager is forbidden
  from granting Admin/Super Admin).
- **Invitation flow tests specifically**: invite creation + mail/notification sent;
  acceptance with a valid token succeeds (user created, role assigned, token consumed);
  expired token rejected; already-used token rejected; mismatched email rejected;
  revoked invite rejected.
- **Unit tests** for testable logic in isolation — enum `label()`, model helpers like
  `Task::isOverdue()`, query scopes (e.g. `overdue`), the token generation/validation
  logic, the role-escalation rule, and any service/action classes.
Cover both happy paths and authorization/validation failure paths.

---

Start with **Phase 1: the plan.** Stop after presenting it.