<?php

namespace Database\Seeders\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Modifiers\ElephantInTheRoom\ElephantRematch;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

/**
 * Creates the two Elephant in the Room game modes: head-to-head (exactly 2
 * players) and practice vs the bot (exactly 1 player — the bot is a virtual
 * actor inside the challenge, not a Player row).
 *
 * Both ship dark (is_public: false, visible to super admins only); flip
 * is_public when ready to launch.
 */
class ElephantInTheRoomSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $lobby_message = '<h1>Elephant in the Room</h1><p>Slide tiles onto a 4x4 board and be the first to arrange four of them into your victory shape. The elephant blocks slides — after every tile you place, you must move it (or leave it right where it is).</p>';

        $mode_id = GameModeAdded::fire(
            name: 'Elephant in the Room',
            description: 'A head-to-head abstract strategy game of sliding tiles and one very stubborn elephant.',
            pre_game_lobby_message: $lobby_message,
            type: 'individual',
            min_players: 2,
            max_players: 2,
            is_public: false,
            players_can_join_late: false,
            scoreboard_type: 'none',
        )->game_mode_id;

        GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'Elephant in the Room Template 1',
            type: 'individual',
            is_public: false,
            team_names: [],
            challenges: [
                [
                    'challenge_keys' => [ElephantMatch::key()],
                    'duration' => 20, // hard ceiling; the match usually ends itself well before
                ],
            ],
            modifiers: [ElephantRematch::key()],
            players_can_join_late: false,
        );

        $bot_mode_id = GameModeAdded::fire(
            name: 'Elephant in the Room (vs Bot)',
            description: 'Practice Elephant in the Room against the bot. Just you, the board, and the elephant.',
            pre_game_lobby_message: $lobby_message,
            type: 'individual',
            min_players: 1,
            max_players: 1,
            is_public: false,
            players_can_join_late: false,
            scoreboard_type: 'none',
        )->game_mode_id;

        GameTemplateAdded::fire(
            game_mode_id: $bot_mode_id,
            name: 'Elephant in the Room vs Bot Template 1',
            type: 'individual',
            is_public: false,
            team_names: [],
            challenges: [
                [
                    'challenge_keys' => [ElephantMatch::key()],
                    'duration' => 20,
                ],
            ],
            modifiers: [ElephantRematch::key()],
            players_can_join_late: false,
        );
    }
}
