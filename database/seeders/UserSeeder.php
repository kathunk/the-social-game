<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Events\UserGainedMembership;
use App\Events\UserPromotedToSuperAdmin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Thunk\Verbs\Facades\Verbs;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Verbs::commitImmediately();

        $admin_data = [
            ['John Rudolph Drexler', 'john@thunk.dev', true],
            ['Jacob Davis', 'jacob@thunk.dev', true],
            ['Daniel Coulbourne', 'daniel@thunk.dev', true],
            ['Jake Bathman', 'jake@thunk.dev', false],
            ['Aaron Belz', 'aaron@thunk.dev', false],
            ['Chris Morrell', 'chris@thunk.dev', false],
            ['Caleb Porzio', 'caleb@thunk.dev', false],
            ['Taylor Otwell', 'taylor@thunk.dev', false],
            ['Josh Hanley', 'josh@thunk.dev', false],
            ['Scammy McGee', 'scammy@thunk.dev', false],
            ['Daniel Coulbourne', 'danie1@thunk.dev', false],
            ['Cedric Daniels', 'cedric@thunk.dev', false],
            ['Jimmy McNulty', 'jimmy@thunk.dev', false],
            ['Bubbles', 'bubbles@thunk.dev', false],
            ['Kima Greggs', 'kima@thunk.dev', false],
            ['Dwayne Pride', 'dwayne@thunk.dev', false],
            ['Leslie', 'leslie@thunk.dev', false],
            ['Gina', 'gina@thunk.dev', false],
            ['Norman', 'norman@thunk.dev', false],
            ['Bunk', 'bunk@thunk.dev', false],
        ];

        foreach ($admin_data as $data) {
            $user = User::fromTemplate($data[0], $data[1], bcrypt('password'));

            if ($data[2]) {
                UserPromotedToSuperAdmin::fire(user_id: $user->id);
                UserGainedMembership::fire(user_id: $user->id);
            }
        }

        // $templates = [
        //     'Laracon 2025' => [
        //         'name' => 'Laracon 2025',
        //         'description' => 'A team game for the Laravel Conference 2025.',
        //         'type' => 'team',
        //         'min_players' => 0,
        //         'max_players' => null,
        //         'is_public' => false,
        //         'team_names' => ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node', 'Python', 'Ruby', 'Go', 'Elixir'],
        //         'challenges' => [
        //             PyramidScheme::key() => 420,
        //             StayOnMessage::key() => 60,
        //         ],
        //         'players_can_join_late' => false,
        //     ]
        // ];

        // foreach ($templates as $name => $template) {
        //     GameTemplateAdded::fire(
        //         name: $name,
        //         description: $template['description'],
        //         type: $template['type'],
        //         min_players: $template['min_players'],
        //         max_players: $template['max_players'],
        //         is_public: $template['is_public'],
        //         team_names: $template['team_names'],
        //         challenges: $template['challenges'],
        //         players_can_join_late: $template['players_can_join_late'],
        //     );
        // }

        // $game = Game::fromTemplate(
        //     template: GameTemplate::firstWhere('name', 'Laracon 2025'),
        //     starts_at: now(),
        //     user: User::firstWhere('email', 'john@thunk.dev'),
        //     is_public: true,
        //     requires_admin_approval_to_join: true,
        // )->start();

        // foreach (User::all() as $user) {
        //     $user->requestToJoinGame($game);
        //     $user->promoteToAdmin($game);
        //     $user->admitToGame($game, $user);
        // }

        // $admin = $game->admins->first();

    }
}
