<?php

use Database\Seeders\UserSeeder;
use Database\Seeders\WarGamesSeeder;
use Database\Seeders\BloodOathSeeder;
use Database\Seeders\Laracon2025Seeder;
use Database\Seeders\PeckingOrderSeeder;
use Database\Seeders\PyramidSchemeSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can seed games', function () {
    $this->seed(UserSeeder::class);
    $this->seed(PeckingOrderSeeder::class);
    $this->seed(Laracon2025Seeder::class);
    $this->seed(BloodOathSeeder::class);
    $this->seed(PyramidSchemeSeeder::class);
    $this->seed(WarGamesSeeder::class);
    $this->assertTrue(true);
});
