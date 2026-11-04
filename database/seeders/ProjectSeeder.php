<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::factory(5)->create();

        $users->each(function ($user) use ($users) {
            $projects = \App\Models\Project::factory(2)->create([
                'user_id' => $user->id,
            ]);

            $projects->each(function ($project) use ($users, $user) {
                $participants = $users->where('id', '!=', $user->id)->random(rand(1, 3));

                foreach ($participants as $participant) {
                    $project->participants()->attach($participant->id, [
                        'role' => fake()->randomElement(['Desarrollador', 'Diseñador', 'Tester', 'Project Manager']),
                        'contribution_hours' => fake()->numberBetween(10, 200),
                    ]);
                }
            });
        });
    }
}
