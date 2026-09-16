<?php

namespace Database\Seeders;

use App\Models\Category;
use Faker\Generator as Faker;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Faker $faker): void
    {
        $categories = [
            'Career',
            'Studies',
            'Bonds',
            'Sports',
            'Cooking',
            'Crafting',
            'Wellness',
            'Travel',
            'Finance',
            'Languages',
            'Culture',
            'Promises'
        ];

        foreach ($categories as $category) {

            $newCategory = new Category();

            $newCategory->name = $category;
            $newCategory->description = $faker->sentence();
            
            $newCategory->save();
        }
    }
}
