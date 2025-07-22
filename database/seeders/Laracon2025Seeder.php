<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Challenges\Classes\FlattenTheCurve;
use App\Challenges\Classes\StayOnMessage;
use App\Challenges\Classes\TeamBounty;
use App\Challenges\Classes\TeamBrinksmanship;
use App\Challenges\Classes\TeamHotPotato;
use App\Challenges\Classes\TeamPrisonersDilemma;
use App\Challenges\Classes\TeamWarmUp;
use App\Challenges\Classes\TheGreatRealignment;
use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Jobs\AddFakeUserToLaraconGame;
use App\Models\Game;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\Modifier;
use App\Models\User;
use App\Modifiers\Classes\TeamRecruiter;
use App\Modifiers\Classes\TeamResignation;
use App\Modifiers\Classes\TeamSecretAlliance;
use App\Modifiers\Classes\TeamSecretCodes;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class Laracon2025Seeder extends Seeder
{
    public function run(): void
    {
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

        Verbs::commit();

        $mode = GameMode::find($mode_id);

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'Laracon 2025',
            is_public: false,
            type: 'team',
            team_names: ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node', 'Python', 'Ruby', 'Go', 'Elixir'],
            challenges: [
                [
                    'challenge_keys' => [FlattenTheCurve::key()],
                    'duration' => 4,
                ],
                [
                    'challenge_keys' => [TeamWarmUp::key()],
                    'duration' => 420,
                ],
                [
                    'challenge_keys' => [StayOnMessage::key()],
                    'duration' => 60,
                ],
                [
                    'challenge_keys' => [TeamPrisonersDilemma::key()],
                    'duration' => 180,
                ],
                [
                    'challenge_keys' => [TeamBounty::key()],
                    'duration' => 540,
                ],
                [
                    'challenge_keys' => [TheGreatRealignment::key()],
                    'duration' => 180,
                ],
                [
                    'challenge_keys' => [TeamHotPotato::key()],
                    'duration' => 60,
                ],
                [
                    'challenge_keys' => [TeamBrinksmanship::key()],
                    'duration' => 120,
                ],
                [
                    'challenge_keys' => [FlattenTheCurve::key()],
                    'duration' => 660,
                ],
            ],
            modifiers: [TeamRecruiter::key(), TeamResignation::key(), TeamSecretAlliance::key(), TeamSecretCodes::key()],
        )->game_template_id;

        Verbs::commit();

        $template = GameTemplate::find($template_id);

        $john = User::firstWhere('email', 'john@thunk.dev');

        $game = Game::fromTemplate(
            template: $template,
            game_mode: $mode,
            starts_at: now(),
            user: $john,
            is_public: true,
            requires_admin_approval_to_join: true,
        );

        $game->start();

        $admins = User::where('is_super_admin', true)->where('email', '!=', 'john@thunk.dev')->get();

        $game->refresh();

        foreach ($admins as $admin) {
            $admin->promoteToGameAdmin($game, $john);
            $admin->requestToJoinGame($game);
            $admin->admitToGame($game, $john);
        }

        $secret_alliance_modifier = Modifier::where('class_key', TeamSecretAlliance::key())->first();

        $teams = $game->teams;

        for ($i = 0; $i < 100; $i++) {
            AddFakeUserToLaraconGame::dispatchSync(
                team: $teams->random(),
                admin: $admin,
                game: $game,
                secret_alliance_modifier: $secret_alliance_modifier,
            );
        }
    }
}
