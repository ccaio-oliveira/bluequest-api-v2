<?php

namespace Database\Seeders;

use App\Models\Challenge;
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
        $fernanda = User::create([
            'name' => 'Fernanda', 'email' => 'fernanda@bluequest.app',
            'password' => Hash::make('password'),
        ]);

        $others = collect(['Ana', 'Caio', 'João'])->map(fn ($name) => User::create([
            'name' => $name,
            'email' => strtolower(str_replace(['ã', 'é'], ['a', 'e'], $name)).'@bluequest.app',
            'password' => Hash::make('password'),
        ]));

        $challenge = Challenge::create([
            'creator_user_id' => $fernanda->id,
            'name' => 'Projeto Verão',
            'description' => 'Desafio de 30 dias',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-30',
            'timezone' => 'America/Sao_Paulo',
        ]);

        foreach ([$fernanda, ...$others] as $user) {
            Participant::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'joined_at' => CarbonImmutable::parse('2026-08-01 08:00'),
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
            'deadline_time' => '22:00', 'photo_requirement' => 'optional',
        ]);

        Task::create([
            'challenge_id' => $challenge->id, 'name' => 'Cardio',
            'points' => 3, 'recurrence_type' => 'weekdays',
            'recurrence_weekdays' => [2, 4, 6],
            'deadline_time' => '20:00', 'photo_requirement' => 'none',
        ]);
    }
}
