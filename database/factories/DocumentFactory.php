<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DealSpace;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->slug().'.pdf';

        return [
            'organization_id' => Organization::factory(),
            'deal_space_id' => DealSpace::factory(),
            'folder_id' => null,
            'owner_user_id' => User::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'filename' => $filename,
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(5000, 5000000),
            'version' => 1,
            'checksum' => fake()->sha256(),
            'metadata' => [
                'source' => 'factory',
                'category' => fake()->randomElement(['financial', 'legal', 'diligence']),
            ],
            'uploaded_at' => now(),
        ];
    }
}
