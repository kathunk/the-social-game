<?php

use Database\Seeders\BloodOathSeeder;
use Database\Seeders\Laracon2025Seeder;
use Database\Seeders\PeckingOrderSeeder;
use Database\Seeders\UserSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can seed games', function () {
    $this->seed(UserSeeder::class);
    $this->seed(Laracon2025Seeder::class);
    $this->seed(PeckingOrderSeeder::class);
    $this->seed(BloodOathSeeder::class);

    $this->assertTrue(true);
});
