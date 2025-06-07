<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Challenges\Classes\IndividualBloodOathHunterQuiz;
use App\Challenges\Classes\IndividualDoubleTrouble;
use App\Challenges\Classes\IndividualHighScoreQuiz;
use App\Challenges\Classes\IndividualOathQuiz;
use App\Challenges\Classes\IndividualOathSpy;
use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Models\Game;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\User;
use App\Modifiers\Classes\BloodOaths;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class BloodOathSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $mode_id = GameModeAdded::fire(
            name: 'Blood Oaths',
            description: 'Play the game with a secret alliance, or as a lone wolf.',
            pre_game_lobby_message: "<h1>A popularity contest for horrible people</h1><h3>Climb to the top of the ranks.</h3><p>Every round, you will upvote and downvote your opponents. But you will also take quizzes about how you expect the votes to turn out. When you are right, you'll accumulate secret points that are revealed at the end of the game. Outsmart your opponents to be at the top of the Pecking Order!</p>",
            type: 'individual',
            min_players: 4,
            max_players: 12,
            is_public: true,
            players_can_join_late: false,
        )->game_mode_id;

        $mode = GameMode::find($mode_id);

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'BO Template 1',
            type: 'individual',
            is_public: true,
            team_names: [],
            challenges: [
                [
                    'challenge_keys' => [
                        IndividualHighScoreQuiz::key(),
                    ],
                    'duration' => 5,
                ],
                [
                    'challenge_keys' => [
                        IndividualOathSpy::key(),
                    ],
                    'duration' => 5,
                ],
                [
                    'challenge_keys' => [
                        IndividualHighScoreQuiz::key(),
                    ],
                    'duration' => 5,
                ],
                [
                    'challenge_keys' => [
                        IndividualOathQuiz::key(),
                    ],
                    'duration' => 5,
                ],
                [
                    'challenge_keys' => [
                        IndividualDoubleTrouble::key(),
                    ],
                    'duration' => 5,
                ],
                [
                    'challenge_keys' => [
                        IndividualBloodOathHunterQuiz::key(),
                    ],
                    'duration' => 5,
                ],
            ],
            modifiers: [BloodOaths::key()],
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
