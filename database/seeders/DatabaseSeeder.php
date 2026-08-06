<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            // Laracon2025\Laracon2025Seeder::class,
            PeckingOrder\PeckingOrderSeeder::class,
            // PeckingOrder\BloodOathSeeder::class,
            // PeckingOrder\PyramidSchemeSeeder::class,
            // TierList\TierListSeeder::class,
            // Farm\FarmSeeder::class,
        ]);
    }
}
