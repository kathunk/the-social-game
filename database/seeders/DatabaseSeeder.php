<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\MorningRoutineSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            // Laracon2025Seeder::class,
            MorningRoutineSeeder::class,
            // PeckingOrderSeeder::class,
            // BloodOathSeeder::class,
            // PyramidSchemeSeeder::class,
        ]);
    }
}
