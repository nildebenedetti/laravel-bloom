<?php

namespace Database\Seeders;

use App\Models\Emotion;
use Faker\Generator as Faker;
use Illuminate\Database\Seeder;

class EmotionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Faker $faker): void
    {
        $emotions = [
            'Proud',
            'Relieved',
            'Excited',
            'Determined',
            'Grateful',
            'Grounded',
            'Cherished'
        ];

        foreach($emotions as $emotion) {
            $newEmotion = new Emotion();

            $newEmotion->name = $emotion;
            $newEmotion->color = $faker->hexColor();

            $newEmotion->save();
        }
    }
}
