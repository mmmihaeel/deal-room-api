<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'actor_user_id' => User::factory(),
            'event' => fake()->randomElement([
                'organization.created',
                'document.created',
                'share-link.created',
                'membership.updated',
            ]),
            'auditable_type' => null,
            'auditable_id' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'context' => ['source' => 'factory'],
            'created_at' => now(),
        ];
    }
}
