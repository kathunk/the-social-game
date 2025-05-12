<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GameTemplateSeeder::class,
            UserSeeder::class,
            Laracon2025Seeder::class,
            PeckingOrderSeeder::class,
            // Laracon2025TeamPlayerSeeder::class,
        ]);
    }
}
