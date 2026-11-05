<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_projects_list(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertOk();
        $response->assertViewIs('projects.index');
    }

    public function test_guest_cannot_view_projects_list(): void
    {
        $response = $this->get('/projects');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_create_project_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertOk();
        $response->assertViewIs('projects.create');
    }

    public function test_authenticated_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $projectData = [
            'title' => 'New Project',
            'description' => 'Project description',
            'due_date' => '2026-12-31',
            'status' => 'pending',
        ];

        $response = $this->actingAs($user)->post('/projects', $projectData);

        $project = Project::where('title', 'New Project')->first();
        $response->assertRedirect("/projects/{$project->id}");
        $this->assertDatabaseHas('projects', [
            'title' => 'New Project',
            'description' => 'Project description',
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_project_title_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/projects', [
            'description' => 'Project description',
            'due_date' => '2026-12-31',
            'status' => 'pending',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_authenticated_user_can_view_project_details(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}");

        $response->assertOk();
        $response->assertViewIs('projects.show');
        $response->assertViewHas('project', $project);
    }

    public function test_user_can_view_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}");

        $response->assertOk();
    }

    public function test_user_cannot_view_other_users_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->get("/projects/{$project->id}");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_edit_project_form(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/edit");

        $response->assertOk();
        $response->assertViewIs('projects.edit');
        $response->assertViewHas('project', $project);
    }

    public function test_project_owner_can_update_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $updatedData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'due_date' => '2027-01-15',
            'status' => 'in_progress',
        ];

        $response = $this->actingAs($user)->put("/projects/{$project->id}", $updatedData);

        $response->assertRedirect("/projects/{$project->id}");
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'status' => 'in_progress',
        ]);
    }

    public function test_user_cannot_update_other_users_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->put("/projects/{$project->id}", [
            'title' => 'Hacked Title',
            'description' => 'Hacked description',
            'due_date' => '2027-01-15',
            'status' => 'in_progress',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('projects', [
            'title' => 'Hacked Title',
        ]);
    }

    public function test_project_owner_can_delete_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/projects/{$project->id}");

        $response->assertRedirect('/projects');
        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->delete("/projects/{$project->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_deleting_project_removes_participants(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->participants()->attach($participant->id, [
            'role' => 'developer',
            'contribution_hours' => 10,
        ]);

        $this->actingAs($owner)->delete("/projects/{$project->id}");

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $participant->id,
        ]);
    }
}
