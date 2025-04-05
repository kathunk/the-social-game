<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Events\UserCreated;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Database\Seeder;
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

        foreach ($admin_data as $data) {
            $user_id = UserCreated::fire(
                name: $data[0],
                email: $data[1],
                encrypted_password: bcrypt('password'),
            )->user_id;

            UserPromotedToAdmin::fire(
                user_id: $user_id,
            );
        }

        $user_data = [
            ['Jake Bathman', 'jake@thunk.dev'],
            ['Aaron Belz', 'aaron@thunk.dev'],
            ['Scammy McGee', 'scammy@thunk.dev'],
            ['Daniel Coulbourne', 'danie1@thunk.dev'],
            ['Chris Morrell', 'chris@thunk.dev'],
            ['Caleb Porzio', 'caleb@thunk.dev'],
            ['Taylor Otwell', 'taylor@thunk.dev'],
        ];

        foreach ($user_data as $data) {
            UserCreated::fire(
                name: $data[0],
                email: $data[1],
                encrypted_password: bcrypt('password'),
            );
        }
    }
}
