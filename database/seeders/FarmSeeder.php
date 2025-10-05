<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Game;
use App\Models\User;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Events\GameModeAdded;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Database\Seeder;
use App\Events\GameTemplateAdded;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmTeams;
use App\Challenges\Classes\FarmRound;
use App\Modifiers\Classes\FarmSkills;
use App\Modifiers\Classes\FarmActions;

class FarmSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $mode_id = GameModeAdded::fire(
            name: 'Farm',
            description: 'Farm stuff',
            pre_game_lobby_message: '<h1>Farm stuff</h1><p>Farm stuff</p>',
            type: 'team',
            min_players: 0,
            max_players: 10000,
            is_public: true,
            players_can_join_late: false,
            scoreboard_type: 'team',
        )->game_mode_id;

        $mode = GameMode::find($mode_id);

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'Farm Template 1',
            type: 'team',
            is_public: true,
            team_names: [],
            challenges: collect(range(1, 28))->map(fn ($i) => [
                'challenge_keys' => [FarmRound::key()],
                'duration' => 1000,
            ])->toArray(),
            modifiers: [FarmTeams::key(), FarmActions::key(), FarmSkills::key(), FarmMap::key()],
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

        $users = User::where('email', '!=', 'john@thunk.dev')->take(10)->get();

        foreach ($users as $user) {
            $user->requestToJoinGame($game);
        }

        $game->start();
    }
}
