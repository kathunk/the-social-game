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
use App\GameTemplates\Laracon2025;
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

        $game = (new Laracon2025())->createGame();

        foreach ($admin_data as $data) {
            $user = User::fromTemplate($data[0], $data[1], bcrypt('password'), $game)
                ->promoteToAdmin($game);
            
            $user->admitToGame($game, $user);
        }

        $admin = $game->admins->first();

        $user_data = [
            ['Jake Bathman', 'jake@thunk.dev'],
            ['Aaron Belz', 'aaron@thunk.dev'],
            ['Chris Morrell', 'chris@thunk.dev'],
            ['Caleb Porzio', 'caleb@thunk.dev'],
            ['Taylor Otwell', 'taylor@thunk.dev'],
            ['Josh Hanley', 'josh@thunk.dev'],
        ];

        foreach ($user_data as $data) {
            User::fromTemplate($data[0], $data[1], bcrypt('password'), $game)
                ->admitToGame($game, $admin);
        }

        $pending_user_data = [
            ['Scammy McGee', 'scammy@thunk.dev'],
            ['Daniel Coulbourne', 'danie1@thunk.dev'],
            ['Cedric Daniels', 'cedric@thunk.dev'],
            ['Jimmy McNulty', 'jimmy@thunk.dev'],
            ['Bubbles', 'bubbles@thunk.dev'],
            ['Kima Greggs', 'kima@thunk.dev'],
            ['Dwayne Pride', 'dwayne@thunk.dev'],
            ['Leslie', 'leslie@thunk.dev'],
            ['Gina', 'gina@thunk.dev'],
            ['Norman', 'norman@thunk.dev'],
            ['Bunk', 'bunk@thunk.dev'],
        ];

        foreach ($pending_user_data as $data) {
            User::fromTemplate($data[0], $data[1], bcrypt('password'), $game);
        }
    }
}
