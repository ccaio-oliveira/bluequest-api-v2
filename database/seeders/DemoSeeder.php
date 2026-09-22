<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\Completion;
use App\Models\Participant;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $fernanda = User::firstOrCreate(
            ['email' => 'fernanda@bluequest.app'],
            ['name' => 'Fernanda', 'password' => Hash::make('password')],
        );

        $others = collect(['Ana', 'Caio', 'João'])->map(fn ($name) => User::firstOrCreate(
            ['email' => strtolower(str_replace(['ã', 'é'], ['a', 'e'], $name)).'@bluequest.app'],
            ['name' => $name, 'password' => Hash::make('password')],
        ));

        $today = CarbonImmutable::now('America/Sao_Paulo');

        $challenge = Challenge::create([
            'creator_user_id' => $fernanda->id,
            'name' => 'Projeto Verão',
            'description' => 'Desafio de 30 dias',
            'start_date' => $today->subDays(10)->toDateString(),
            'end_date' => $today->addDays(19)->toDateString(),
            'timezone' => 'America/Sao_Paulo',
        ]);

        foreach ([$fernanda, ...$others] as $user) {
            Participant::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'joined_at' => $today->subDays(10),
            ]);
        }

        Task::create([
            'challenge_id' => $challenge->id, 'name' => 'Beber 2L de água',
            'points' => 2, 'recurrence_type' => 'daily',
            'deadline_time' => '23:59', 'photo_requirement' => 'none',
        ]);

        Task::create([
            'challenge_id' => $challenge->id, 'name' => 'Fazer treino',
            'points' => 5, 'recurrence_type' => 'weekdays',
            'recurrence_weekdays' => [2, 3, 5, 6],
            'deadline_time' => '22:00', 'photo_requirement' => 'required',
        ]);

        Task::create([
            'challenge_id' => $challenge->id, 'name' => 'Cardio',
            'points' => 3, 'recurrence_type' => 'weekdays',
            'recurrence_weekdays' => [2, 4, 6],
            'deadline_time' => '20:00', 'photo_requirement' => 'none',
        ]);

        $tasks = $challenge->tasks;
        $participants = $challenge->participants;

        foreach ($participants as $index => $participant) {
            foreach (range(1, 9) as $daysAgo) {
                $date = $today->subDays($daysAgo);

                foreach ($tasks as $task) {
                    if (($daysAgo + $index) % 3 === 0 || !$task->recurrence()->occursOn($date)) {
                        continue;
                    }

                    Completion::create([
                        'participant_id' => $participant->id,
                        'task_id' => $task->id,
                        'occurrence_date' => $date->toDateString(),
                        'completed_at' => $date->setTime(20, 0),
                        'points_awarded' => $task->points,
                    ]);
                }
            }
        }
    }
}
