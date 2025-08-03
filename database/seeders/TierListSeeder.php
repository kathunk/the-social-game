<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Challenges\Classes\IndividualChoosePointsOrHidden;
use App\Challenges\Classes\IndividualChooseSafetyOrDanger;
use App\Challenges\Classes\IndividualDoubleTrouble;
use App\Challenges\Classes\IndividualEquilibrium;
use App\Challenges\Classes\IndividualFewestHiddenPointQuiz;
use App\Challenges\Classes\IndividualFirstShallBeLast;
use App\Challenges\Classes\IndividualGerrymander;
use App\Challenges\Classes\IndividualHighScoreQuiz;
use App\Challenges\Classes\IndividualHighTrustEnvironment;
use App\Challenges\Classes\IndividualMostHiddenPointQuiz;
use App\Challenges\Classes\IndividualSpy;
use App\Challenges\Classes\TierListConstructionPhase;
use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Models\Game;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\User;
use App\Modifiers\Classes\Alms;
use App\Modifiers\Classes\TierListModifier;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class TierListSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $mode_id = GameModeAdded::fire(
            name: 'Tier List',
            description: 'Rank stuff',
            pre_game_lobby_message: "<h1>Rank stuff</h1><p>Rank stuff</p>",
            type: 'individual',
            min_players: 2,
            max_players: 10,
            is_public: true,
            players_can_join_late: false,
        )->game_mode_id;

        $mode = GameMode::find($mode_id);

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'Tier List Template 1',
            type: 'individual',
            is_public: true,
            team_names: [],
            challenges: [
                [
                    'challenge_keys' => [
                        TierListConstructionPhase::key(),
                    ],
                    'duration' => null,
                ],
            ],
            modifiers: [TierListModifier::key()],
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
