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
use App\Challenges\Classes\MorningRoutineChallenge;
use App\Modifiers\Classes\MorningRoutineProgress;

class MorningRoutineSeeder extends Seeder
{
    public function run(): void
    {
        $mode_id = GameModeAdded::fire(
            name: 'Morning Routine',
            description: 'A team game for the Morning Routine.',
            pre_game_lobby_message: "<h1>Welcome to the Morning Routine</h1><p>Do it to it.</p>",
            type: 'individual',
            min_players: 3,
            max_players: 10,
            is_public: true,
            players_can_join_late: false,
        )->game_mode_id;

        Verbs::commit();

        $mode = GameMode::find($mode_id);

        $challenges = [];

        for ($i = 0; $i < 16; $i++) {
            $challenges[] = [
                'challenge_keys' => [MorningRoutineChallenge::key()],
                'duration' => 1,
            ];
        }

        $template_id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: 'Morning Routine',
            is_public: true,
            type: 'individual',
            team_names: [],
            challenges: $challenges,
            modifiers: [MorningRoutineProgress::key()],
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

        $opponents = User::where('email', '!=', 'john@thunk.dev')->limit(3)->get();

        foreach ($opponents as $opponent) {
            $opponent->requestToJoinGame($game);
            $opponent->admitToGame($game, $john);
        }

        $game->start();
    }
}
