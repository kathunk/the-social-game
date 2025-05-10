<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BountySeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::transaction(function () {
                Verbs::commitImmediately();

                $john = User::firstWhere('email', 'john@thunk.dev');

                $game = Game::firstWhere('name', 'Laracon 2025');

                $data = [
                    ['Harry Potter', 'harry@thunk.dev'],
                    ['Luke Skywalker', 'luke@thunk.dev'],
                    ['Sherlock Holmes', 'sherlock@thunk.dev'],
                    ['Tony Stark', 'tony@thunk.dev'],
                    ['Bruce Wayne', 'bruce@thunk.dev'],
                    ['Peter Parker', 'peter@thunk.dev'],
                    ['Frodo Baggins', 'frodo@thunk.dev'],
                    ['James Bond', 'james@thunk.dev'],
                    ['Indiana Jones', 'indy@thunk.dev'],
                    ['Wonder Woman', 'diana@thunk.dev'],
                    ['Captain America', 'steve@thunk.dev'],
                    ['Gandalf', 'gandalf@thunk.dev'],
                    ['Hermione Granger', 'hermione@thunk.dev'],
                    ['Black Widow', 'blackwidow@thunk.dev'],
                    ['Doctor Strange', 'strange@thunk.dev'],
                    ['Princess Leia', 'leia@thunk.dev'],
                    ['Thor Odinson', 'thor@thunk.dev'],
                ];

                collect($data)->map(function ($user) use ($game, $john) {
                    return User::fromTemplate($user[0], $user[1], bcrypt('password'))
                        ->requestToJoinGame($game)
                        ->admitToGame($game, $john);
                });

                $game->players->each(function ($player, $index) use ($game) {
                    $teamIndex = floor($index / 3);
                    $player->joinTeam($game->teams[$teamIndex]);
                });
            });
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
