<?php

namespace Database\Seeders;

use App\Models\Game;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Events\GameCreated;
use App\Events\UserCreated;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Database\Seeder;
use App\Events\UserAdmittedToGame;
use App\Events\UserPromotedToAdmin;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $admin_data = [
            ['John Rudolph Drexler', 'john@thunk.dev'],
            ['Jacob Davis', 'jacob@thunk.dev'],
            ['Daniel Coulbourne', 'daniel@thunk.dev'],
        ];

        $game_id = GameCreated::fire(
            name: 'Test Game',
        )->game_id;

        Verbs::commit();

        $game = Game::find($game_id);

        foreach ($admin_data as $data) {
            $user = User::fromTemplate($data[0], $data[1], bcrypt('password'), $game)
                ->applyToGame($game)
                ->promoteToAdmin($game);
            
            $user->admitToGame($game, $user);
        }

        $admin = $game->admins->first();

        $user_data = [
            ['Jake Bathman', 'jake@thunk.dev'],
            ['Aaron Belz', 'aaron@thunk.dev'],
            ['Scammy McGee', 'scammy@thunk.dev'],
            ['Daniel Coulbourne', 'danie1@thunk.dev'],
            ['Chris Morrell', 'chris@thunk.dev'],
            ['Caleb Porzio', 'caleb@thunk.dev'],
            ['Taylor Otwell', 'taylor@thunk.dev'],
            ['Will King', 'will@thunk.dev'],
        ];

        foreach ($user_data as $data) {
            User::fromTemplate($data[0], $data[1], bcrypt('password'), $game)
                ->applyToGame($game)
                ->admitToGame($game, $admin);
        }
    }
}
