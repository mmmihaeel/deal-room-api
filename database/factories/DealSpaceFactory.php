<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DealSpaceStatus;
use App\Models\DealSpace;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealSpace>
 */
class DealSpaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'created_by_user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'external_reference' => strtoupper(fake()->bothify('DS-#####')),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(DealSpaceStatus::values()),
        ];
    }
}
