<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Game;
use App\Models\GameTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class PeckingOrderSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $template = GameTemplate::firstWhere('name', 'Pecking Order');

        $john = User::firstWhere('email', 'john@thunk.dev');

        $game = Game::fromTemplate(
            template: $template,
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
