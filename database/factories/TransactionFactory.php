<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => \App\Models\Employee::factory()->create()->id(),
            'document_id' => \App\Models\Document::factory()->create()->id(),
            'action' => $this->faker->sentence(),
            'status' => rand(1, 2),
        ];
    }
}
