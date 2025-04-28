<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Database\Seeder;
use App\Events\GameTemplateAdded;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\StayOnMessage;

class GameTemplateSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $templates = [
            'Laracon 2025' => [
                'description' => 'A team game for the Laravel Conference 2025.',
                'pre_game_lobby_message' => 'Welcome to the Laravel Conference 2025!',
                'type' => 'team',
                'min_players' => 0,
                'max_players' => null,
                'is_public' => false,
                'team_names' => ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node', 'Python', 'Ruby', 'Go', 'Elixir'],
                'challenges' => [
                    PyramidScheme::key() => 420,
                    StayOnMessage::key() => 60,
                ],
                'players_can_join_late' => true,
            ],
        ];

        foreach ($templates as $name => $template) {
            GameTemplateAdded::fire(
                name: $name,
                description: $template['description'],
                pre_game_lobby_message: $template['pre_game_lobby_message'],
                type: $template['type'],
                min_players: $template['min_players'],
                max_players: $template['max_players'],
                is_public: $template['is_public'],
                team_names: ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node', 'Python', 'Ruby', 'Go', 'Elixir'],
                challenges: [
                    [
                        'challenge_keys' => [PyramidScheme::key()],
                        'duration' => 420,
                    ],
                    [
                        'challenge_keys' => [StayOnMessage::key()],
                        'duration' => 60,
                    ],
                ],
                players_can_join_late: true,
            );
        }
    }
}
