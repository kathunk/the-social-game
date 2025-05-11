<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Game;
use App\Models\User;
use App\Models\GameTemplate;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Database\Seeder;

class Laracon2025Seeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $template = GameTemplate::firstWhere('name', 'Laracon 2025');

        $john = User::firstWhere('email', 'john@thunk.dev');

        $game = Game::fromTemplate(
            template: $template,
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
