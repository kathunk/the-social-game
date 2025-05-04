<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Game;
use App\Models\User;
use App\Models\GameTemplate;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeckingOrderSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::transaction(function () {
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

                $users = User::all()->slice(12);

                foreach ($users as $user) {
                    $user->requestToJoinGame($game);
                }

                $game->start();
            });
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
