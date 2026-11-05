<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_add_participant(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->post("/projects/{$project->id}/participants", [
            'user_id' => $participant->id,
            'role' => 'developer',
            'contribution_hours' => 20,
        ]);

        $response->assertRedirect("/projects/{$project->id}");
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $participant->id,
            'role' => 'developer',
            'contribution_hours' => 20,
        ]);
    }

    public function test_non_owner_cannot_add_participant(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->post("/projects/{$project->id}/participants", [
            'user_id' => $participant->id,
            'role' => 'developer',
            'contribution_hours' => 20,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $participant->id,
        ]);
    }

    public function test_cannot_add_same_participant_twice(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->participants()->attach($participant->id, [
            'role' => 'developer',
            'contribution_hours' => 10,
        ]);

        $response = $this->actingAs($owner)->post("/projects/{$project->id}/participants", [
            'user_id' => $participant->id,
            'role' => 'designer',
            'contribution_hours' => 20,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_project_owner_can_update_participant_hours(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->participants()->attach($participant->id, [
            'role' => 'developer',
            'contribution_hours' => 10,
        ]);

        $response = $this->actingAs($owner)->patch("/projects/{$project->id}/participants/{$participant->id}/hours", [
            'contribution_hours' => 25,
        ]);

        $response->assertRedirect("/projects/{$project->id}");
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $participant->id,
            'contribution_hours' => 25,
        ]);
    }

    public function test_non_owner_cannot_update_participant_hours(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->participants()->attach($participant->id, [
            'role' => 'developer',
            'contribution_hours' => 10,
        ]);

        $response = $this->actingAs($otherUser)->patch("/projects/{$project->id}/participants/{$participant->id}/hours", [
            'contribution_hours' => 25,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $participant->id,
            'contribution_hours' => 10,
        ]);
    }

    public function test_contribution_hours_must_be_positive(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->post("/projects/{$project->id}/participants", [
            'user_id' => $participant->id,
            'role' => 'developer',
            'contribution_hours' => -5,
        ]);

        $response->assertSessionHasErrors('contribution_hours');
    }

    public function test_project_owner_can_remove_participant(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->participants()->attach($participant->id, [
            'role' => 'developer',
            'contribution_hours' => 10,
        ]);

        $response = $this->actingAs($owner)->delete("/projects/{$project->id}/participants/{$participant->id}");

        $response->assertRedirect("/projects/{$project->id}");
        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $participant->id,
        ]);
    }

    public function test_non_owner_cannot_remove_participant(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->participants()->attach($participant->id, [
            'role' => 'developer',
            'contribution_hours' => 10,
        ]);

        $response = $this->actingAs($otherUser)->delete("/projects/{$project->id}/participants/{$participant->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $participant->id,
        ]);
    }

    public function test_participant_can_view_project_they_participate_in(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $project->participants()->attach($participant->id, [
            'role' => 'developer',
            'contribution_hours' => 10,
        ]);

        $response = $this->actingAs($participant)->get("/projects/{$project->id}");

        $response->assertOk();
    }

    public function test_project_shows_all_participants(): void
    {
        $owner = User::factory()->create();
        $participants = User::factory()->count(3)->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        foreach ($participants as $index => $participant) {
            $project->participants()->attach($participant->id, [
                'role' => 'developer',
                'contribution_hours' => ($index + 1) * 10,
            ]);
        }

        $response = $this->actingAs($owner)->get("/projects/{$project->id}");

        $response->assertOk();
        foreach ($participants as $participant) {
            $response->assertSee($participant->name);
        }
    }
}
