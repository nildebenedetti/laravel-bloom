<?php

namespace Database\Seeders;

use App\Models\Record;
use Illuminate\Database\Seeder;
use Faker\Generator as Faker;

class RecordsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Faker $faker): void
    {
        for ($i = 0; $i < 10; $i++) {
            $newRecord = new Record();
            $coin = rand(0, 1);

            $newRecord->title = $faker->words(4, true); // true means unique string
            $newRecord->description = $faker->paragraphs(4, true);
            $newRecord->date = $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
            // omitted image_path
            $newRecord->visibility = $coin === 0 ? 'public' : 'private';
            $newRecord->category_id = rand(1, 12);

            $newRecord->save();
        }
    }
}
