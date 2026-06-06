---
name: igxtelle-coding-style
description: Coding style conventions for the IGXTelle Law Group task management app (Laravel 13 + Inertia + Vue 3 + shadcn-vue). Apply whenever writing or reviewing PHP, Vue, TypeScript, config, or route files in this project.
---

# IGXTelle Coding Style

This project is a **Laravel 13 + Inertia.js + Vue 3 (Composition API, TypeScript) + shadcn-vue** monolith. Authentication is already implemented via Laravel Fortify (2FA + passkeys). **This is NOT a JSON:API project** — controllers return Inertia responses, not JSON resources.

Apply these rules to every file you touch.

---

## PHP Conventions

### Import Ordering (length, shortest first)

Enforced via `pint.json`:

```json
{
    "preset": "laravel",
    "rules": {
        "ordered_imports": {
            "sort_algorithm": "length"
        },
        "binary_operator_spaces": {
            "operators": {
                "=>": "align_single_space"
            }
        }
    }
}
```

```php
use App\Models\Task;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Project;
use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
```

### Arrow Alignment (`=>`)

Align `=>` with `align_single_space` in all associative arrays — `rules()`, `casts()`, Inertia prop arrays, etc.

```php
return [
    'title'       => ['required', 'string', 'max:255'],
    'project_id'   => ['required', 'exists:projects,id'],
    'assigned_to' => ['nullable', 'exists:users,id'],
    'priority'    => ['required', Rule::enum(TaskPriority::class)],
    'due_date'    => ['nullable', 'date'],
];
```

### HTTP Status Codes — SymfonyResponse Constants

Never hardcode HTTP status integers in the rare JSON endpoints (e.g. drag-and-drop status updates, notification reads). Inertia page responses don't need this, but JSON responses do.

```php
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return response()->json(['ok' => true], SymfonyResponse::HTTP_OK);
```

---

## The Inertia Response Layer

This replaces Tayla's JSON:API layer entirely.

### Controllers return `Inertia::render()`

Controllers render a **page component** (matching `resources/js/pages/...`) and pass typed props. Keep controllers thin — push query logic into the model, query scopes, or a dedicated service/action.

```php
public function index(Request $request): Response
{
    return Inertia::render('projects/Index', [
        'projects' => ProjectResource::collection(
            Project::query()
                ->with('lead')
                ->withCount(['tasks', 'completedTasks'])
                ->latest()
                ->paginate(15)
        ),
    ]);
}
```

### Resources are still used — but as plain array shapers

We DO use API Resource classes (`JsonResource`) to keep prop shapes consistent between controllers, but they return plain arrays via `toArray()` (NOT JsonApiResource / `toType()` / `toAttributes()`).

```php
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'due_date'    => $this->due_date,
            'is_overdue'  => $this->isOverdue(),
            'assignee'    => UserResource::make($this->whenLoaded('assignee')),
            'project'      => ProjectResource::make($this->whenLoaded('project')),
        ];
    }
}
```

### Redirects, not JSON, for writes

Store/update/destroy actions **redirect back** with a flash message. Inertia surfaces validation errors automatically — no `failedValidation()` override needed.

```php
public function store(StoreTaskRequest $request): RedirectResponse
{
    Task::create($request->validated());

    return back()->with('success', 'Task created.');
}
```

### Shared data

Global data (auth user, roles, unread notification count, flash messages) lives in `app/Http/Middleware/HandleInertiaRequests.php` under `share()`. Don't re-pass these per-controller.

---

## Folder Structure

No `Api/V1` namespace — this is a web app. Group by **domain**.

### Controllers
```
app/Http/Controllers/
├── Controller.php
├── DashboardController.php
├── Project/
│   └── ProjectController.php
├── Task/
│   ├── TaskController.php
│   └── TaskStatusController.php   // drag-and-drop status updates
└── Notification/
    └── NotificationController.php
```

### Form Requests
```
app/Http/Requests/
├── Project/
│   ├── StoreProjectRequest.php
│   └── UpdateProjectRequest.php
└── Task/
    ├── StoreTaskRequest.php
    └── UpdateTaskRequest.php
```

### Resources
```
app/Http/Resources/
├── User/UserResource.php
├── Project/ProjectResource.php
└── Task/TaskResource.php
```

---

## Route Segmentation

Routes are split across levels (web app version of the Tayla pattern):

1. `routes/web.php` — entry point; requires the aggregate:

```php
require __DIR__.'/definitions/web/web-routes.php';
```

2. `routes/definitions/web/web-routes.php` — aggregator:

```php
require __DIR__.'/sections/project-routes.php';
require __DIR__.'/sections/task-routes.php';
require __DIR__.'/sections/dashboard-routes.php';
```

3. `routes/definitions/web/sections/{domain}-routes.php` — definitions:

```php
use App\Http\Controllers\Task as TaskControllers;

Route::middleware(['auth'])->group(function (): void {
    Route::resource('tasks', TaskControllers\TaskController::class);

    Route::patch('tasks/{task}/status', [TaskControllers\TaskStatusController::class, 'update'])
        ->name('tasks.status.update');
});
```

### Namespace Aliases
When a route file uses multiple controllers from one namespace, import the namespace with a `use ... as` alias: domain PascalCase + `Controllers` suffix (`Task as TaskControllers`).

### Middleware Formatting
Single middleware on one line; multiple as a multiline array.

```php
Route::middleware('auth')->group(function (): void {
Route::middleware(['auth', 'role:admin'])->group(function (): void {
```

---

## Model Conventions

### `#[Guarded]` over `#[Fillable]`
```php
#[Guarded(['id'])]
```
Import: `use Illuminate\Database\Eloquent\Attributes\Guarded;`

### UUID Primary Keys
All models use `HasUuids` (UUIDv7). `id` defined as `uuid('id')->primary()` in migrations.

### Member Ordering
1. PHP attributes (`#[Guarded]`, `#[Hidden]`)
2. `use` trait statement
3. `#[Scope]` query scopes
4. Relationship methods
5. Other public methods, then protected/private
6. `casts()` method
7. `booted()` if needed

```php
#[Guarded(['id'])]
class Task extends Model
{
    use HasFactory, HasUuids;

    #[Scope]
    public function overdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->where('status', '!=', TaskStatus::DONE);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== TaskStatus::DONE;
    }

    protected function casts(): array
    {
        return [
            'status'       => TaskStatus::class,
            'priority'     => TaskPriority::class,
            'due_date'     => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
```

---

## Enums

Every enum implements `label(): string`. Backed enums use UpperCase keys and UpperCase_Snake_Case string values.

```php
enum TaskStatus: string
{
    case TODO        = 'TODO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case REVIEW      = 'REVIEW';
    case DONE        = 'DONE';

    public function label(): string
    {
        return match ($this) {
            self::TODO        => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::REVIEW      => 'Review',
            self::DONE        => 'Done',
        };
    }
}
```

Expose enum options to the frontend via a shared prop or a dedicated endpoint so Vue selects/columns stay in sync with the backend.

---

## Form Requests

- Dedicated Form Request class for every write — never validate inline.
- Extend `Illuminate\Foundation\Http\FormRequest` directly (Inertia handles error redirects natively — no custom `failedValidation()`).
- Implement `authorize()` properly using policies/roles (this IS a web app with real auth).
- Array rule syntax (`['required', 'string']`), never pipe syntax.
- Align `=>`.
- Put `Rule::` instances on their own line.

```php
public function authorize(): bool
{
    return $this->user()->can('create', Task::class);
}

public function rules(): array
{
    return [
        'title'       => ['required', 'string', 'max:255'],
        'project_id'   => ['required', 'exists:projects,id'],
        'assigned_to' => ['nullable', 'exists:users,id'],
        'status'      => ['required', Rule::enum(TaskStatus::class)],
        'priority'    => ['required', Rule::enum(TaskPriority::class)],
        'due_date'    => ['nullable', 'date'],
    ];
}
```

---

## Authorization

Use **Policies** for every model (`ProjectPolicy`, `TaskPolicy`) and `spatie/laravel-permission` roles. Gate routes with `role:`/`can:` middleware AND check in `authorize()`. Never rely on hiding UI alone.

---

## Frontend — Vue 3 + Inertia + shadcn-vue + TypeScript

### Always use the shadcn-vue MCP
Before hand-rolling any UI primitive (button, dialog, dropdown, table, card, badge, select, etc.), **add it via the shadcn-vue MCP**. Do not write custom components for things shadcn-vue provides. Compose pages from shadcn primitives.

### Component style
- `<script setup lang="ts">` — Composition API only. No Options API.
- Strongly type all props with `defineProps<T>()` and an interface.
- `<script setup>` first, then `<template>`. No `<style>` blocks unless unavoidable — use Tailwind utilities.

```vue
<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import type { Task } from '@/types'

interface Props {
    task: Task
}

defineProps<Props>()
</script>

<template>
    <Card>
        <CardHeader>{{ task.title }}</CardHeader>
        <CardContent>
            <Badge :variant="task.is_overdue ? 'destructive' : 'secondary'">
                {{ task.status }}
            </Badge>
        </CardContent>
    </Card>
</template>
```

### Frontend folder structure
```
resources/js/
├── pages/              // Inertia page components (PascalCase), mirror controller render paths
│   ├── Dashboard.vue
│   ├── projects/
│   │   ├── Index.vue
│   │   └── Show.vue
│   └── tasks/
│       └── Index.vue
├── components/
│   ├── ui/             // shadcn-vue generated primitives — do not hand-edit
│   ├── task/           // domain components (TaskCard.vue, KanbanBoard.vue)
│   └── project/
├── layouts/            // AppLayout.vue, AuthLayout.vue (starter kit)
├── composables/        // useFilters, useTaskForm, etc.
└── types/              // index.ts — shared TS types matching backend resources
```

## Laravel Wayfinder

This project uses **Laravel Wayfinder** to bridge backend routes/controllers to fully-typed TypeScript functions. **Never hardcode URLs or use the Ziggy-style `route()` helper** — always import the generated Wayfinder function.

### Setup (already configured — do not re-add)
- Vite plugin `@laravel/vite-plugin-wayfinder` is registered in `vite.config.ts`, so definitions regenerate on file changes during dev and at build time.
- Generated output lives in `resources/js/wayfinder`, `resources/js/actions`, and `resources/js/routes`.
- These three directories are **`.gitignore`d** and fully regenerated — never hand-edit or commit them.
- Manual regeneration when needed:
  ```bash
  php artisan wayfinder:generate
  ```

### Importing — prefer named routes, fall back to actions

Two import sources are available. **Default to named-route imports** (`@/routes/...`) for navigation and links — they're stable and read cleanly. Use **action imports** (`@/actions/...`) when you want the controller-method binding explicitly (e.g. forms tied to a specific controller action).

```ts
// Named route (preferred for links/navigation)
import { show } from '@/routes/tasks'
show(taskId) // { url: "/tasks/1", method: "get" }

// Action (controller-method bound — good for forms)
import { store, update } from '@/actions/App/Http/Controllers/Task/TaskController'
store()        // { url: "/tasks", method: "post" }
update(taskId) // { url: "/tasks/1", method: "patch" }
```

> If two routes point at the same action, the action export becomes a URI-keyed dictionary and is **not** callable directly. In that case, import by named route from `@/routes/...` instead.

### Argument shapes
Wayfinder accepts flexible argument shapes — use whichever is clearest:

```ts
show(props.task.id)
show({ id: props.task.id })
update([props.task.id, props.project.id])
update({ task: props.task.id, project: props.project.id })
```

For bound keys (`/projects/{project:slug}`): `show('my-project')` or `show({ slug: 'my-project' })`.

### URL-only and method variants
```ts
show.url(1)       // "/tasks/1"
show.head(1)      // { url: "/tasks/1", method: "head" }
```

### Query parameters
Pass a final `options` argument with `query` (or `mergeQuery` to combine with the current URL):

```ts
import { index } from '@/routes/tasks'

index.url(undefined, {
    query: { status: 'IN_PROGRESS', sort_by: 'due_date' },
})
// "/tasks?status=IN_PROGRESS&sort_by=due_date"

// Merge with existing params; null removes a param
index.url(undefined, { mergeQuery: { page: 2, status: null } })
```

This is the canonical way to build filtered/paginated/sorted list URLs — never string-concatenate query params.

### Wayfinder + Inertia (Vue)

Pass a Wayfinder result directly to `useForm().submit()` — it resolves both URL and method:

```ts
import { useForm } from '@inertiajs/vue3'
import { store } from '@/actions/App/Http/Controllers/Task/TaskController'

const form = useForm({
    title: '',
    project_id: '',
    priority: 'MEDIUM',
    due_date: null,
})

const submit = () => form.submit(store())   // POST /tasks
```

For updates, pass the bound action:

```ts
import { update } from '@/actions/App/Http/Controllers/Task/TaskController'

const submit = () => form.submit(update(props.task.id)) // PATCH /tasks/{id}
```

Use Wayfinder results in `<Link>` as well:

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { show } from '@/routes/tasks'
</script>

<template>
    <Link :href="show(task.id)">View task</Link>
</template>
```

### Deploy note
Wayfinder reads from the registered router, so a stale cached route table produces missing/incorrect definitions. Any deploy step must run `php artisan route:clear` **before** `npm run build` (the Vite plugin invokes `wayfinder:generate` during build). If the deploy ends with `php artisan optimize`/`route:cache`, ensure the next build clears first.


### Naming
- Page & component files: `PascalCase.vue`.
- Composables: `useThing.ts` (camelCase).
- Inertia render strings match folder paths: `Inertia::render('tasks/Index')` → `resources/js/pages/tasks/Index.vue`.
- **Never use the `route()` helper or hardcoded URL strings anywhere in the frontend** — all URLs come from Wayfinder imports (@/routes/... or @/actions/...).

### Types mirror backend resources
Keep `resources/js/types/index.ts` in sync with the PHP Resource shapes. Every prop a controller passes should have a TS interface.

```ts
export interface Task {
    id: string
    title: string
    status: 'TODO' | 'IN_PROGRESS' | 'REVIEW' | 'DONE'
    priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT'
    due_date: string | null
    is_overdue: boolean
    assignee: User | null
}
```

### Forms
Use Inertia's `useForm()` for all create/edit forms, and submit with a **Wayfinder action** via `form.submit()` (never `route()` strings):

```ts
import { useForm } from '@inertiajs/vue3'
import { store } from '@/actions/App/Http/Controllers/Task/TaskController'

const form = useForm({
    title: '',
    project_id: '',
    priority: 'MEDIUM',
    due_date: null,
})

const submit = () => form.submit(store())
```

### Kanban drag-and-drop
Use `vuedraggable` (vue.draggable.next). On drop, submit with the Wayfinder action:
`form.submit(updateStatus(task.id))` (imported from `@/actions/...TaskStatusController`) with the new status. Update optimistically and roll back on error.

---

## Formatting & Verification

After PHP changes, run Pint:
```bash
vendor/bin/pint --dirty --format agent
```

After frontend changes, run the linter/formatter:
```bash
npm run lint
npm run format
```

`pint.json` config:
- `laravel` preset
- Import sorting by length (`sort_algorithm: length`)
- `=>` alignment (`align_single_space`)