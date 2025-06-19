<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\TeamBrinksmanship;
use App\Challenges\Classes\TeamHotPotato;
use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Models\Game;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\User;
use App\Modifiers\Classes\TeamResignation;
use App\Modifiers\Classes\TeamSecretAlliance;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class Laracon2025Seeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $mode_id = GameModeAdded::fire(
            name: 'Laracon 2025',
            description: 'A team game for the Laravel Conference 2025.',
            pre_game_lobby_message: "<h1>Welcome to the Laracon 2025 Pyramid Scheme</h1><h2>Brought to you by <a target=\"_blank\" rel=\"noopener noreferrer nofollow\" href=\"https://thunk.dev\">Thunk</a></h2><h3>Find the man with the bag of cash. He will let you into the game.</h3><p>The pyramid scheme will take place for the duration of the conference. At 5pm at the end of the conference, the team at the top of the leader board will split $1,500. There will be twists and turns along the way. To keep up with the latest, follow <a target=\"_blank\" rel=\"noopener noreferrer nofollow\" href=\"https://twitter.com/johnrudolphdrex\">John's Twitter</a>.</p><h3><strong>You're using your real name, right?</strong></h3><p>To join the game, your name must match your Laracon badge.</p>",
            type: 'team',
            min_players: 0,
            max_players: null,
            is_public: true,
            players_can_join_late: true,
        )->game_mode_id;

        $mode = GameMode::find($mode_id);

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'Laracon 2025',
            is_public: false,
            type: 'team',
            team_names: ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node', 'Python', 'Ruby', 'Go', 'Elixir'],
            challenges: [
                [
                    'challenge_keys' => [PyramidScheme::key()],
                    'duration' => 10000,
                ],
                [
                    'challenge_keys' => [TeamBrinksmanship::key()],
                    'duration' => 10000,
                ],
                [
                    'challenge_keys' => [TeamHotPotato::key()],
                    'duration' => 60,
                ],
            ],
            modifiers: [TeamResignation::key(), TeamSecretAlliance::key()],
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
