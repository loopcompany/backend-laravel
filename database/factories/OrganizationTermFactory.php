<?php

namespace Database\Factories;

use App\Models\OrganizationTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationTerm>
 */
class OrganizationTermFactory extends Factory
{
    protected $model = OrganizationTerm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
        ];
    }
}
