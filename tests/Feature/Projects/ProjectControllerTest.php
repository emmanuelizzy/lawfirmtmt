<?php

use App\Models\User;
use App\Models\Project;
use App\Enums\ProjectStatus;

use function Pest\Laravel\get;
use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->superAdmin = User::factory()->create()->assignRole('Super Admin');
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->opsManager = User::factory()->create()->assignRole('Operations Manager');
    $this->staff = User::factory()->create()->assignRole('Staff');
});

// ── Index ────────────────────────────────────────────────────────────────────

test('guests are redirected from projects index', function (): void {
    get(route('projects.index'))->assertRedirect(route('login'));
});

test('all roles can view projects index', function (): void {
    foreach ([$this->superAdmin, $this->admin, $this->opsManager, $this->staff] as $user) {
        actingAs($user)->get(route('projects.index'))->assertOk();
    }
});

test('projects index returns correct inertia component', function (): void {
    Project::factory(3)->create();

    actingAs($this->admin)
        ->get(route('projects.index'))
        ->assertInertia(fn ($page) => $page
            ->component('projects/Index')
            ->has('projects.data', 3)
        );
});

// ── Create / Store ───────────────────────────────────────────────────────────

test('staff cannot access create project page', function (): void {
    actingAs($this->staff)
        ->get(route('projects.create'))
        ->assertForbidden();
});

test('authorized roles can access create project page', function (): void {
    foreach ([$this->superAdmin, $this->admin, $this->opsManager] as $user) {
        actingAs($user)->get(route('projects.create'))->assertOk();
    }
});

test('can store a project with valid data', function (): void {
    $lead = User::factory()->create();

    actingAs($this->opsManager)
        ->post(route('projects.store'), [
            'title'       => 'New Case',
            'description' => 'Details here',
            'lead_id'     => $lead->id,
            'status'      => ProjectStatus::ACTIVE->value,
        ])
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success');

    expect(Project::where('title', 'New Case')->exists())->toBeTrue();
});

test('store validates required fields', function (): void {
    actingAs($this->admin)
        ->post(route('projects.store'), [])
        ->assertSessionHasErrors(['title', 'lead_id', 'status']);
});

test('staff cannot store a project', function (): void {
    $lead = User::factory()->create();

    actingAs($this->staff)
        ->post(route('projects.store'), [
            'title'   => 'Sneaky Project',
            'lead_id' => $lead->id,
            'status'  => ProjectStatus::ACTIVE->value,
        ])
        ->assertForbidden();
});

// ── Show ─────────────────────────────────────────────────────────────────────

test('all roles can view a project', function (): void {
    $project = Project::factory()->create();

    foreach ([$this->superAdmin, $this->admin, $this->opsManager, $this->staff] as $user) {
        actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('projects/Show'));
    }
});

// ── Edit / Update ─────────────────────────────────────────────────────────────

test('staff cannot access project edit page', function (): void {
    $project = Project::factory()->create();

    actingAs($this->staff)
        ->get(route('projects.edit', $project))
        ->assertForbidden();
});

test('authorized roles can update a project', function (): void {
    $project = Project::factory()->create();
    $lead = User::factory()->create();

    actingAs($this->opsManager)
        ->patch(route('projects.update', $project), [
            'title'   => 'Updated Title',
            'lead_id' => $lead->id,
            'status'  => ProjectStatus::ON_HOLD->value,
        ])
        ->assertRedirect(route('projects.show', $project))
        ->assertSessionHas('success');

    expect($project->fresh()->title)->toBe('Updated Title');
});

test('staff cannot update a project', function (): void {
    $project = Project::factory()->create();
    $lead = User::factory()->create();

    actingAs($this->staff)
        ->patch(route('projects.update', $project), [
            'title'   => 'Hacked',
            'lead_id' => $lead->id,
            'status'  => ProjectStatus::ACTIVE->value,
        ])
        ->assertForbidden();
});

// ── Destroy ──────────────────────────────────────────────────────────────────

test('super admin and admin can delete a project', function (): void {
    foreach ([$this->superAdmin, $this->admin] as $user) {
        $project = Project::factory()->create();

        actingAs($user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success');

        expect(Project::find($project->id))->toBeNull();
    }
});

test('operations manager cannot delete a project', function (): void {
    $project = Project::factory()->create();

    actingAs($this->opsManager)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();
});

test('staff cannot delete a project', function (): void {
    $project = Project::factory()->create();

    actingAs($this->staff)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();
});
