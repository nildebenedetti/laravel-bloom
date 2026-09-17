<?php

namespace Database\Seeders;

use Database\Seeders\CategoriesTableSeeder;
use Database\Seeders\EmotionsTableSeeder;
use Database\Seeders\RecordsTableSeeder;
use Database\Seeders\TiersTableSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            CategoriesTableSeeder::class,
            TiersTableSeeder::class,
            RecordsTableSeeder::class,
            EmotionsTableSeeder::class
        ]);
    }
}
