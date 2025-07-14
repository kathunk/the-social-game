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
use App\Modifiers\Classes\WarGamesMap;
use App\Challenges\Classes\WarGamesChallenge;

class WarGamesSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $mode_id = GameModeAdded::fire(
            name: 'War Games',
            description: 'Big team battle',
            pre_game_lobby_message: "pre game lobby message",
            type: 'team',
            min_players: 0,
            max_players: null,
            is_public: true,
            players_can_join_late: true,
        )->game_mode_id;

        $mode = GameMode::find($mode_id);

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'War Games',
            is_public: false,
            type: 'team',
            team_names: ['Team 1'],
            challenges: [
                [
                    'challenge_keys' => [WarGamesChallenge::key()],
                    'duration' => 10000,
                ],
            ],
            modifiers: [WarGamesMap::key()],
        )->game_template_id;

        $template = GameTemplate::find($template_id);

        $john = User::firstWhere('email', 'john@thunk.dev');

        $game = Game::fromTemplate(
            template: $template,
            game_mode: $mode,
            starts_at: now(),
            user: $john,
            is_public: true,
            requires_admin_approval_to_join: true,
        )->start();

        $admins = User::where('is_super_admin', true)->where('email', '!=', 'john@thunk.dev')->get();

        $game->refresh();

        foreach ($admins as $admin) {
            $admin->promoteToGameAdmin($game, $john);
            $admin->requestToJoinGame($game);
            $admin->admitToGame($game, $john);
        }

        $normies = User::where('is_super_admin', false)->get();

        $accepted_normies = $normies->slice(10);

        $teams = $game->teams;

        foreach ($accepted_normies as $normie) {
            $normie->requestToJoinGame($game);
            $normie->admitToGame($game, $john);
            $normie->fresh()->currentPlayer->joinTeam($teams->random());
        }

        $stragglers = $normies->slice(0, 9);

        foreach ($stragglers as $straggler) {
            $straggler->requestToJoinGame($game);
        }
    }
}
