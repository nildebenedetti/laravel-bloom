<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Record;
use App\Models\Tier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Record>
 */
class RecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->sentence(),
            'description' => $this->faker->text(),
            'date' => $this->faker->date(),
            'image_path' => $this->faker->url(),
            'image_alt' => $this->faker->sentence(),
            'visibility' => $this->faker->randomElement(['public', 'private']),
            'category_id' => Category::all()->random()->id,
            'tier_id' => Tier::all()->random()->id,
            'user_id' => User::all()->random()->id,
        ];
    }
}
