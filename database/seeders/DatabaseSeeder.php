<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Game;
use App\Models\User;
use App\Models\GameTemplate;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Database\Seeder;
use App\Events\GameTemplateAdded;
use App\GameTemplates\Laracon2025;
use App\Console\Commands\SeedProduction;
use App\Events\UserPromotedToSuperAdmin;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\StayOnMessage;
use App\Events\UserGainedMembership;

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

        $templates = [
            'Laracon 2025' => [
                'name' => 'Laracon 2025',
                'description' => 'A team game for the Laravel Conference 2025.',
                'type' => 'team',
                'min_players' => 0,
                'max_players' => null,
                'is_public' => false,
                'team_names' => ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node', 'Python', 'Ruby', 'Go', 'Elixir'],
                'challenges' => [
                    PyramidScheme::key() => 420,
                    StayOnMessage::key() => 60,
                ],
            ]
        ];

        foreach ($templates as $name => $template) {
            GameTemplateAdded::fire(
                name: $name,
                description: $template['description'],
                type: $template['type'],
                min_players: $template['min_players'],
                max_players: $template['max_players'],
                is_public: $template['is_public'],
                team_names: $template['team_names'],
                challenges: $template['challenges'],
            );
        }

        foreach ($admin_data as $data) {
            $user = User::fromTemplate($data[0], $data[1], bcrypt('password'));
            UserPromotedToSuperAdmin::fire(user_id: $user->id);
            UserGainedMembership::fire(user_id: $user->id);
        }

        $game = Game::fromTemplate(
            template: GameTemplate::firstWhere('name', 'Laracon 2025'),
            starts_at: now(),
            user: User::firstWhere('email', 'john@thunk.dev'),
        )->start();

        foreach (User::all() as $user) {
            $user->applyToGame($game);
            $user->promoteToAdmin($game);
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
