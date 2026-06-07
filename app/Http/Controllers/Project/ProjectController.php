<?php

namespace App\Http\Controllers\Project;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Project;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Http\Resources\Task\TaskResource;
use App\Http\Resources\Project\ProjectResource;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Project::class);

        return Inertia::render('projects/Index', [
            'projects' => ProjectResource::collection(
                Project::query()
                    ->with('lead')
                    ->withCount('tasks')
                    ->latest()
                    ->paginate(15)
            ),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('projects/Create', [
            'statuses' => collect(ProjectStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        Project::create($request->validated());

        return to_route('projects.index')->with('success', 'Project created.');
    }

    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('projects/Show', [
            'project'    => ProjectResource::make($project->load('lead'))->resolve(),
            'tasks'      => $project->tasks()
                ->with('assignee')
                ->orderByRaw("CASE priority WHEN 'URGENT' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'MEDIUM' THEN 3 WHEN 'LOW' THEN 4 ELSE 5 END")
                ->orderBy('created_at')
                ->get()
                ->map(fn ($task) => TaskResource::make($task)->resolve()),
            'users'      => User::orderBy('name')->get(['id', 'name']),
            'statuses'   => collect(TaskStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'priorities' => collect(TaskPriority::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('projects/Edit', [
            'project'  => ProjectResource::make($project->load('lead'))->resolve(),
            'statuses' => collect(ProjectStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return to_route('projects.show', $project)->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return to_route('projects.index')->with('success', 'Project deleted.');
    }
}
