<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\BloodOathSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            // Laracon2025Seeder::class,
            // PeckingOrderSeeder::class,
            BloodOathSeeder::class,
        ]);
    }
}
