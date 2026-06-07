<?php

namespace App\Http\Controllers\Task;

use App\Models\Task;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Project;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Http\Resources\Task\TaskResource;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;

class TaskController extends Controller
{
    public function index(Project $project): Response
    {
        $this->authorize('viewAny', Task::class);

        return Inertia::render('tasks/Index', [
            'project' => [
                'id'    => $project->id,
                'title' => $project->title,
            ],
            'tasks' => TaskResource::collection(
                $project->tasks()
                    ->with('assignee')
                    ->latest()
                    ->paginate(20)
            ),
        ]);
    }

    public function create(Project $project): Response
    {
        $this->authorize('create', Task::class);

        return Inertia::render('tasks/Create', [
            'project'    => [
                'id'    => $project->id,
                'title' => $project->title,
            ],
            'statuses'   => collect(TaskStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'priorities' => collect(TaskPriority::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        $project->tasks()->create($request->validated());

        return to_route('projects.tasks.index', $project)->with('success', 'Task created.');
    }

    public function edit(Project $project, Task $task): Response
    {
        $this->authorize('update', $task);

        return Inertia::render('tasks/Edit', [
            'project'    => [
                'id'    => $project->id,
                'title' => $project->title,
            ],
            'task'       => TaskResource::make($task->load('assignee')),
            'statuses'   => collect(TaskStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'priorities' => collect(TaskPriority::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return to_route('projects.tasks.index', $task->project)->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return to_route('projects.tasks.index', $task->project)->with('success', 'Task deleted.');
    }
}
