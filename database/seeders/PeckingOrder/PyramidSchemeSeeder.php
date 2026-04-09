<?php

namespace Database\Seeders\PeckingOrder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Challenges\Classes\PeckingOrder\IndividualBuddySystem;
use App\Challenges\Classes\PeckingOrder\IndividualChooseHopeOrFear;
use App\Challenges\Classes\PeckingOrder\IndividualChoosePointsOrHidden;
use App\Challenges\Classes\PeckingOrder\IndividualChooseSafetyOrDanger;
use App\Challenges\Classes\PeckingOrder\IndividualDoubleTrouble;
use App\Challenges\Classes\PeckingOrder\IndividualGrandstandGambit;
use App\Challenges\Classes\PeckingOrder\IndividualHighScoreQuiz;
use App\Challenges\Classes\PeckingOrder\IndividualStealTheBacon;
use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Models\Game;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\User;
use App\Modifiers\Classes\PeckingOrder\IndividualRecruiter;
use App\Modifiers\Classes\PeckingOrder\IndividualResignation;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class PyramidSchemeSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $mode_id = GameModeAdded::fire(
            name: 'Pyramid Scheme',
            description: 'A pyramid scheme.',
            pre_game_lobby_message: "<h1>A pyramid scheme for horrible people</h1><h3>Climb to the top of the ranks.</h3><p>Every round, you will upvote and downvote your opponents. But you will also take quizzes about how you expect the votes to turn out. When you are right, you'll accumulate secret points that are revealed at the end of the game. Outsmart your opponents to be at the top of the Pyramid Scheme!</p>",
            type: 'individual',
            min_players: 0,
            max_players: 10000,
            is_public: false,
            players_can_join_late: true,
        )->game_mode_id;

        $mode = GameMode::find($mode_id);

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'Pyramid Scheme Template 1',
            type: 'individual',
            is_public: true,
            team_names: [],
            challenges: [
                [
                    'challenge_keys' => [
                        IndividualHighScoreQuiz::key(),
                    ],
                    'duration' => 10,
                ],
                [
                    'challenge_keys' => [
                        IndividualStealTheBacon::key(),
                    ],
                    'duration' => 10,
                ],
                [
                    'challenge_keys' => [
                        IndividualChooseHopeOrFear::key(),
                    ],
                    'duration' => 10,
                ],
                [
                    'challenge_keys' => [
                        IndividualBuddySystem::key(),
                    ],
                    'duration' => 10,
                ],
                [
                    'challenge_keys' => [
                        IndividualChooseSafetyOrDanger::key(),
                    ],
                    'duration' => 1,
                ],
                [
                    'challenge_keys' => [
                        IndividualGrandstandGambit::key(),
                    ],
                    'duration' => 1,
                ],
                [
                    'challenge_keys' => [
                        IndividualChoosePointsOrHidden::key(),
                    ],
                    'duration' => 10,
                ],

                [
                    'challenge_keys' => [
                        IndividualDoubleTrouble::key(),
                    ],
                    'duration' => 10,
                ],
            ],
            modifiers: [IndividualRecruiter::key(), IndividualResignation::key()],
            players_can_join_late: false,
        )->game_template_id;

        $template = GameTemplate::find($template_id);

        $john = User::firstWhere('email', 'john@thunk.dev');

        $game = Game::fromTemplate(
            template: $template,
            game_mode: $mode,
            starts_at: now(),
            user: $john,
            is_public: false,
            requires_admin_approval_to_join: false,
        );

        $game->refresh();

        $users = User::where('email', '!=', 'john@thunk.dev')->take(11)->get();

        foreach ($users as $user) {
            $user->requestToJoinGame($game);
        }

        $game->start();
    }
}
