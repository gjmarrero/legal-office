<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => \App\Models\Client::factory()->create()->id(),
            'date_received' => $this->faker->date(),
            'type' => $this->faker->sentence(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => rand(1,2),
            'remarks' => $this->faker->paragraph(),
        ];
    }
}
