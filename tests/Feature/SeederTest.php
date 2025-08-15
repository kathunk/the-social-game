<?php

use Database\Seeders\DatabaseSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can seed games', function () {
    $this->seed(DatabaseSeeder::class);
    $this->assertTrue(true);
});
