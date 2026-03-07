<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DealPermission;
use App\Models\DealSpace;
use App\Models\DealSpacePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealSpacePermission>
 */
class DealSpacePermissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'deal_space_id' => DealSpace::factory(),
            'user_id' => User::factory(),
            'permission' => fake()->randomElement(DealPermission::values()),
            'created_by_user_id' => null,
        ];
    }
}
