<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Events\UserGainedMembership;
use App\Events\UserPromotedToSuperAdmin;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $super_admins = [
            ['John Rudolph Drexler', 'john@thunk.dev',],
            ['Jacob Davis', 'jacob@thunk.dev'],
            ['Daniel Coulbourne', 'daniel@thunk.dev'],
            ['Chris Thornton', 'chris@thunk.dev'],
        ];

        $users = [
            ['Jake Bathman', 'jake@thunk.dev'],
            ['Aaron Belz', 'aaron@thunk.dev'],
            ['Caleb Porzio', 'caleb@thunk.dev'],
            ['Taylor Otwell', 'taylor@thunk.dev'],
            ['Josh Hanley', 'josh@thunk.dev'],
            ['Scammy McGee', 'scammy@thunk.dev'],
            ['Cedric Daniels', 'cedric@thunk.dev'],
            ['Jimmy McNulty', 'jimmy@thunk.dev'],
            ['Bubbles', 'bubbles@thunk.dev'],
            ['Kima Greggs', 'kima@thunk.dev'],
            ['Dwayne Pride', 'dwayne@thunk.dev'],
            ['Leslie', 'leslie@thunk.dev'],
            ['Gina', 'gina@thunk.dev'],
            ['Norman', 'norman@thunk.dev'],
            ['Bunk', 'bunk@thunk.dev'],
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
            ['Doctor Strange', 'doctor@thunk.dev'],
            ['Princess Leia', 'leia@thunk.dev'],
            ['Thor Odinson', 'thor@thunk.dev'],
            ['Darth Vader', 'darth@thunk.dev'],
            ['Han Solo', 'han@thunk.dev'],
            ['Yoda', 'yoda@thunk.dev'],
            ['Jyn Erso', 'jyn@thunk.dev'],
            ['Rey', 'rey@thunk.dev'],
            ['Chewbacca', 'Chewbacca@thunk.dev'],
            ['Obi-Wan Kenobi', 'obi@thunk.dev'],
            ['Anakin Skywalker', 'anakin@thunk.dev'],
            ['Padme Amidala', 'padme@thunk.dev'],
            ['Mace Windu', 'mace@thunk.dev'],
        ];

        foreach ($super_admins as $admin) {
            $user = User::fromTemplate($admin[0], $admin[1], bcrypt('password'));

            UserPromotedToSuperAdmin::fire(user_id: $user->id);
            UserGainedMembership::fire(user_id: $user->id);
        }

        foreach ($users as $user) {
            $user = User::fromTemplate($user[0], $user[1], bcrypt('password'));
        }
    }
}
