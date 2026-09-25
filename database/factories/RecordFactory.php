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
            // safty closure: if no resource is available, we create one on the go
            'user_id' => fn () => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'category_id' => fn () => Category::inRandomOrder()->first()?->id ?? Category::factory()->create()->id,
            'tier_id' => fn () => Tier::inRandomOrder()->first()?->id ?? Tier::factory()->create()->id,
        ];
    }
    // this method gets to be executed once the record is created in the factory
    public function configure(): static{
        // calls the hook which contains a callback function executed right after record creation
        // responsible for matching the freshly created record id with emotions ids
        return $this->afterCreating(function (Record $record){
            // fetch 1 to 3 IDs from emotion table
            $emotions = \App\Models\Emotion::inRandomOrder()->take(rand(1, 3))->pluck('id');
            // Attach  emotion IDs to the record by creating rows in the pivot table (emotion_record)
            $record->emotions()->attach($emotions);
        });
        
    }
}
