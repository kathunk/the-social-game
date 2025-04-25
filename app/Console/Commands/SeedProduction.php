<?php

namespace App\Console\Commands;

use App\Events\UserCreated;
use Illuminate\Console\Command;
use App\Events\GameTemplateAdded;
use App\Events\UserPromotedToSuperAdmin;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\StayOnMessage;

class SeedProduction extends Command
{
    protected $signature = 'app:seed-production';

    protected $description = 'Seed the production database';

    public function handle()
    {
        $user_id = UserCreated::fire(
            name: 'John Rudolph Drexler',
            email: 'john@thunk.com',
            password: bcrypt('password'),
        )->user_id;

        UserPromotedToSuperAdmin::fire($user_id);

        $templates = [
            'Laracon 2025' => [
                'name' => 'Laracon 2025',
                'type' => 'team',
                'min_players' => 0,
                'max_players' => null,
                'is_public' => false,
                'team_names' => ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node', 'Python', 'Ruby', 'Go', 'Elixir'],
                'challenges' => [
                    PyramidScheme::class => 420,
                    StayOnMessage::class => 60,
                ],
            ],
            'Pecking Order' => [
                'name' => 'Pecking Order',
                'type' => 'individual',
                'min_players' => 6,
                'max_players' => 12,
                'is_public' => false,
                'team_names' => [],
                'challenges' => [],
            ],
        ];

        foreach ($templates as $name => $template) {
            GameTemplateAdded::fire(
                name: $name,
                type: $template['type'],
                min_players: $template['min_players'],
                max_players: $template['max_players'],
                is_public: $template['is_public'],
                team_names: $template['team_names'],
                challenges: $template['challenges'],
            );
        }
    }
}
