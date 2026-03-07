<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Models\DealSpace;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DealSpaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_list_deal_spaces_with_filters(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['owner_user_id' => $user->id]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => MembershipRole::ADMIN,
        ]);

        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/v1/deal-spaces', [
            'organization_id' => $organization->id,
            'name' => 'Series B Room',
            'status' => 'active',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Series B Room')
            ->assertJsonPath('data.status', 'active');

        DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $user->id,
            'name' => 'Closed Room',
            'status' => 'closed',
        ]);

        $listResponse = $this->getJson('/api/v1/deal-spaces?organization_id='.$organization->id.'&status=active');

        $listResponse->assertOk();
        $names = collect($listResponse->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Series B Room'));
        $this->assertFalse($names->contains('Closed Room'));
    }

    public function test_non_member_cannot_view_deal_space(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $organization = Organization::factory()->create(['owner_user_id' => $owner->id]);
        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($stranger);

        $response = $this->getJson('/api/v1/deal-spaces/'.$dealSpace->id);

        $response->assertForbidden();
    }
}
