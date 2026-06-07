<?php

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;

use function Pest\Laravel\get;
use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->superAdmin = User::factory()->create()->assignRole('Super Admin');
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->opsManager = User::factory()->create()->assignRole('Operations Manager');
    $this->staff = User::factory()->create()->assignRole('Staff');
    $this->project = Project::factory()->create();
});

// ── Index ─────────────────────────────────────────────────────────────────────

test('guests are redirected from tasks index', function (): void {
    get(route('projects.tasks.index', $this->project))->assertRedirect(route('login'));
});

test('all roles can view tasks index', function (): void {
    foreach ([$this->superAdmin, $this->admin, $this->opsManager, $this->staff] as $user) {
        actingAs($user)
            ->get(route('projects.tasks.index', $this->project))
            ->assertOk();
    }
});

test('tasks index returns correct inertia component', function (): void {
    Task::factory(3)->for($this->project)->create();

    actingAs($this->admin)
        ->get(route('projects.tasks.index', $this->project))
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks.data', 3)
            ->where('project.id', $this->project->id)
        );
});

// ── Create / Store ────────────────────────────────────────────────────────────

test('staff cannot access create task page', function (): void {
    actingAs($this->staff)
        ->get(route('projects.tasks.create', $this->project))
        ->assertForbidden();
});

test('authorized roles can access create task page', function (): void {
    foreach ([$this->superAdmin, $this->admin, $this->opsManager] as $user) {
        actingAs($user)
            ->get(route('projects.tasks.create', $this->project))
            ->assertOk();
    }
});

test('can store a task with valid data', function (): void {
    $assignee = User::factory()->create();

    actingAs($this->opsManager)
        ->post(route('projects.tasks.store', $this->project), [
            'title'       => 'Draft contract',
            'description' => 'Review and sign',
            'assigned_to' => $assignee->id,
            'status'      => TaskStatus::TODO->value,
            'priority'    => TaskPriority::HIGH->value,
            'due_date'    => now()->addDays(7)->toDateString(),
        ])
        ->assertRedirect(route('projects.tasks.index', $this->project))
        ->assertSessionHas('success');

    expect(Task::where('title', 'Draft contract')->exists())->toBeTrue();
});

test('store validates required fields', function (): void {
    actingAs($this->admin)
        ->post(route('projects.tasks.store', $this->project), [])
        ->assertSessionHasErrors(['title', 'status', 'priority']);
});

test('staff cannot store a task', function (): void {
    actingAs($this->staff)
        ->post(route('projects.tasks.store', $this->project), [
            'title'    => 'Sneaky Task',
            'status'   => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
        ])
        ->assertForbidden();
});

// ── Edit / Update ─────────────────────────────────────────────────────────────

test('staff cannot access task edit page', function (): void {
    $task = Task::factory()->for($this->project)->create();

    actingAs($this->staff)
        ->get(route('tasks.edit', $task))
        ->assertForbidden();
});

test('authorized roles can update a task', function (): void {
    $task = Task::factory()->for($this->project)->create();
    $assignee = User::factory()->create();

    actingAs($this->opsManager)
        ->patch(route('tasks.update', $task), [
            'title'       => 'Updated title',
            'status'      => TaskStatus::IN_PROGRESS->value,
            'priority'    => TaskPriority::HIGH->value,
            'assigned_to' => $assignee->id,
        ])
        ->assertRedirect(route('projects.tasks.index', $this->project))
        ->assertSessionHas('success');

    expect($task->fresh()->title)->toBe('Updated title');
});

test('staff cannot update a task they do not own', function (): void {
    $task = Task::factory()->for($this->project)->create();

    actingAs($this->staff)
        ->patch(route('tasks.update', $task), [
            'title'    => 'Hacked',
            'status'   => TaskStatus::DONE->value,
            'priority' => TaskPriority::LOW->value,
        ])
        ->assertForbidden();
});

// ── Destroy ───────────────────────────────────────────────────────────────────

test('authorized roles can delete a task', function (): void {
    foreach ([$this->superAdmin, $this->admin, $this->opsManager] as $user) {
        $task = Task::factory()->for($this->project)->create();

        actingAs($user)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('projects.tasks.index', $this->project))
            ->assertSessionHas('success');

        expect(Task::find($task->id))->toBeNull();
    }
});

test('staff cannot delete a task', function (): void {
    $task = Task::factory()->for($this->project)->create();

    actingAs($this->staff)
        ->delete(route('tasks.destroy', $task))
        ->assertForbidden();
});
