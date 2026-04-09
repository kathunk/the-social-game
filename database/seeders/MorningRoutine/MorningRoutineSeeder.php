<?php

namespace Database\Seeders\MorningRoutine;

use App\Challenges\MorningRoutine\MorningRoutineRound;
use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Models\Game;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class MorningRoutineSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $mode_id = GameModeAdded::fire(
            name: 'Morning Routine',
            description: 'A push-your-luck game about sharing a house and not leaving a mess.',
            pre_game_lobby_message: '<h1>Morning Routine</h1><p>Navigate rooms in a shared house. Be quick, be clean, and don\'t get caught leaving a mess.</p>',
            type: 'individual',
            min_players: 2,
            max_players: 8,
            is_public: true,
            players_can_join_late: false,
            scoreboard_type: 'hide_until_end',
        )->game_mode_id;

        $mode = GameMode::find($mode_id);

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'Morning Routine Template 1',
            type: 'individual',
            is_public: true,
            team_names: [],
            challenges: [
                [
                    'challenge_keys' => [MorningRoutineRound::key()],
                    'duration' => 5, // 5 minutes
                ],
            ],
            modifiers: [],
            players_can_join_late: false,
        )->game_template_id;

        $template = GameTemplate::find($template_id);

        $john = User::firstWhere('email', 'john@thunk.dev');

        $game = Game::fromTemplate(
            template: $template,
            game_mode: $mode,
            user: $john,
            is_public: false,
            requires_admin_approval_to_join: false,
        );

        $game->refresh();

        $users = User::where('email', '!=', 'john@thunk.dev')->take(3)->get();

        foreach ($users as $user) {
            $user->requestToJoinGame($game);
        }

        $game->start();
    }
}
