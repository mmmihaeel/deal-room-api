<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DealSpace;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'deal_space_id' => DealSpace::factory(),
            'parent_id' => null,
            'created_by_user_id' => User::factory(),
            'name' => ucfirst(fake()->words(2, true)),
        ];
    }
}
