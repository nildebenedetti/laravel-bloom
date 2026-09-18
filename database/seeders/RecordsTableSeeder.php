<?php

namespace Database\Seeders;

use App\Models\Emotion;
use App\Models\Record;
use Faker\Generator as Faker;
use Illuminate\Database\Seeder;

class RecordsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Faker $faker): void
    {
        $emotionsIds = Emotion::pluck('id')->toArray();

        for ($i = 0; $i < 10; $i++) {
            $newRecord = new Record();
            $coin = rand(0, 1);

            $newRecord->title = $faker->words(4, true); // true means unique string
            $newRecord->description = $faker->paragraphs(4, true);
            $newRecord->date = $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
            // omitted image_path
            $newRecord->visibility = $coin === 0 ? 'public' : 'private';
            $newRecord->category_id = rand(1, 12);
            $newRecord->tier_id = rand(1, 4);

            $newRecord->save();

            $randomEmotions = $faker->randomElements($emotionsIds, rand(2, 5));

            $newRecord->emotions()->attach($randomEmotions);
        }
    }
}
