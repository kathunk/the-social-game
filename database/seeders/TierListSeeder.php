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
use App\Challenges\Classes\TierListGuess;
use App\Modifiers\Classes\TierListModifier;
use App\Challenges\Classes\TierListConstructionPhase;

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
                [
                    'challenge_keys' => [
                        TierListGuess::key(),
                    ],
                    'duration' => null,
                ],
                [
                    'challenge_keys' => [
                        TierListGuess::key(),
                    ],
                    'duration' => null,
                ],
                [
                    'challenge_keys' => [
                        TierListGuess::key(),
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

        foreach ($users->take(2) as $user) {
            $user->requestToJoinGame($game);
        }

        $game->start();

        $this->simulateFirstRound($game);
    }

    private function simulateFirstRound(Game $game)
    {
        $construction_challenge = $game->challenges->first();
        $categories = $construction_challenge->challenge_data['categories'];

        $game->players->each(function ($player) use ($game, $categories, $construction_challenge) {
            foreach ($categories as $category) {
                $submissions = [
                    $category . '-A' => $player->name . '-' . $category . '-A',
                    $category . '-B' => $player->name . '-' . $category . '-B',
                    $category . '-C' => $player->name . '-' . $category . '-C',
                    $category . '-D' => $player->name . '-' . $category . '-D',
                    $category . '-F' => $player->name . '-' . $category . '-F',
                ];

                $construction_challenge->handler()->submitTierList($player, $submissions);
            }
        });

        dd($construction_challenge->fresh()->challenge_data);
    }
}
