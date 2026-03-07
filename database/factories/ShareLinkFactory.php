<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DealSpace;
use App\Models\Document;
use App\Models\Organization;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShareLink>
 */
class ShareLinkFactory extends Factory
{
    public function definition(): array
    {
        $token = Str::random(64);

        return [
            'organization_id' => Organization::factory(),
            'deal_space_id' => DealSpace::factory(),
            'document_id' => Document::factory(),
            'created_by_user_id' => User::factory(),
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 12),
            'expires_at' => now()->addHours(24),
            'max_downloads' => fake()->optional()->numberBetween(1, 20),
            'download_count' => 0,
            'revoked_at' => null,
            'last_accessed_at' => null,
        ];
    }
}
