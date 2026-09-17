<?php

namespace Database\Seeders;

use App\Models\Tier;
use Illuminate\Database\Seeder;
use Faker\Generator as Faker;

class TiersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Faker $faker): void
    {
        $tiers = [
            'small win',
            'solid step',
            'major milestone',
            'epic breakthrough'
        ];

        foreach($tiers as $tier) {
            $newTier = new Tier();

            $newTier->name = $tier;
            $newTier->description = $faker->sentence();

            $newTier->save();
        }
    }
}
