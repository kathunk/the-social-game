<?php

use App\Models\Game;
use Database\Seeders\UserSeeder;
use Database\Seeders\Laracon2025Seeder;
use Database\Seeders\GameTemplateSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can seed the Laracon 2025 game', function () {
    $this->seed(UserSeeder::class);
    $this->seed(GameTemplateSeeder::class);
    $this->seed(Laracon2025Seeder::class);

    expect(Game::count())->toBe(1);
});
