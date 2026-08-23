<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_name' => fake()->company(),
            'organization_code' => 'ORG-' . strtoupper(fake()->bothify('????-####')),
            'organization_phone' => '021' . fake()->numerify('########'),
            'organization_address' => fake()->address(),
            'manager_full_name' => fake()->name(),
            'manager_national_code' => fake()->numerify('##########'),
            'profile_image' => null,
            'profile_status' => 'pending',
            'profile_approved_at' => null,
            'profile_rejection_reason' => null,
            'contract_status' => 'pending',
            'contract_approved_at' => null,
            'contract_rejection_reason' => null,
        ];
    }

    /**
     * Indicate that the organization profile is approved.
     */
    public function profileApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_status' => 'approved',
            'profile_approved_at' => now(),
            'profile_rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the organization profile is rejected.
     */
    public function profileRejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_status' => 'rejected',
            'profile_approved_at' => null,
            'profile_rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the organization contract is approved.
     */
    public function contractApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_status' => 'approved',
            'contract_approved_at' => now(),
            'contract_rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the organization contract is rejected.
     */
    public function contractRejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_status' => 'rejected',
            'contract_approved_at' => null,
            'contract_rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the organization has complete access (both profile and contract approved).
     */
    public function completeAccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_status' => 'approved',
            'profile_approved_at' => now(),
            'profile_rejection_reason' => null,
            'contract_status' => 'approved',
            'contract_approved_at' => now(),
            'contract_rejection_reason' => null,
        ]);
    }
}
