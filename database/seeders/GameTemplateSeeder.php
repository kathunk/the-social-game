<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Challenges\Classes\IndividualFewestHiddenPointQuiz;
use App\Challenges\Classes\IndividualHighScoreQuiz;
use App\Challenges\Classes\IndividualLargestDecreaseQuiz;
use App\Challenges\Classes\IndividualLargestIncreaseQuiz;
use App\Challenges\Classes\IndividualLowScoreQuiz;
use App\Challenges\Classes\IndividualMostHiddenPointQuiz;
use App\Challenges\Classes\IndividualNoScoreChangeQuiz;
use App\Challenges\Classes\IndividualSpecificScoreQuiz;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\TeamBounty;
use App\Events\GameTemplateAdded;
use App\Modifiers\Classes\TeamResignation;
use App\Modifiers\Classes\TeamSecretAlliance;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class GameTemplateSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $templates = [
            'Laracon 2025' => [
                'description' => 'A team game for the Laravel Conference 2025.',
                'pre_game_lobby_message' => "<h1>Welcome to the Laracon 2025 Pyramid Scheme</h1><h2>Brought to you by <a target=\"_blank\" rel=\"noopener noreferrer nofollow\" href=\"https://thunk.dev\"><strong><u>Thunk</u></a></h2><h3>Find the man with the bag of cash. He will let you into the game.</h3><p>The pyramid scheme will take place for the duration of the conference. At 5pm at the end of the conference, the team at the top of the leader board will split $1,500. There will be twists and turns along the way. To keep up with the latest, follow <a target=\"_blank\" rel=\"noopener noreferrer nofollow\" href=\"https://twitter.com/johnrudolphdrex\"><strong><u>John's Twitter</u></strong></a>.</p><h3><strong>You're using your real name, right?</strong></h3><p>To join the game, your name must match your Laracon badge.</p>",
                'type' => 'team',
                'min_players' => 0,
                'max_players' => null,
                'is_public' => false,
                'team_names' => ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node', 'Python', 'Ruby', 'Go', 'Elixir'],
                'challenges' => [
                    [
                        'challenge_keys' => [PyramidScheme::key()],
                        'duration' => 1,
                    ],
                    [
                        'challenge_keys' => [TeamBounty::key()],
                        'duration' => 60,
                    ],
                ],
                'modifiers' => [
                    TeamResignation::key(),
                    TeamSecretAlliance::key(),
                ],
                'players_can_join_late' => true,
            ],
            'Pecking Order' => [
                'description' => 'The original!',
                'pre_game_lobby_message' => "<h1>A popularity contest for horrible people</h1><h3>Climb to the top of the ranks.</h3><p>Every round, you will upvote and downvote your opponents. But you will also take quizzes about how you expect the votes to turn out. When you are right, you'll accumulate secret points that are revealed at the end of the game. Outsmart your opponents to be at the top of the Pecking Order!</p>",
                'type' => 'individual',
                'min_players' => 6,
                'max_players' => 12,
                'is_public' => false,
                'team_names' => [],
                'challenges' => [
                    [
                        'challenge_keys' => [IndividualHighScoreQuiz::key(), IndividualLowScoreQuiz::key(), IndividualSpecificScoreQuiz::key(), IndividualNoScoreChangeQuiz::key()],
                        'duration' => 5,
                    ],
                    [
                        'challenge_keys' => [IndividualHighScoreQuiz::key(), IndividualLargestDecreaseQuiz::key(), IndividualLargestIncreaseQuiz::key()],
                        'duration' => 5,
                    ],
                    [
                        'challenge_keys' => [IndividualMostHiddenPointQuiz::key(), IndividualFewestHiddenPointQuiz::key()],
                        'duration' => 5,
                    ],
                ],
                'modifiers' => [],
                'players_can_join_late' => false,
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
                team_names: $template['team_names'],
                challenges: $template['challenges'],
                modifiers: $template['modifiers'],
                players_can_join_late: $template['players_can_join_late'],
            );
        }
    }
}
