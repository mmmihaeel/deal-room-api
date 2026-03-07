<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_organization(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/organizations', [
            'name' => 'Delta Partners',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Delta Partners')
            ->assertJsonPath('data.membership_role', MembershipRole::OWNER->value);

        $organizationId = (int) $response->json('data.id');

        $this->assertDatabaseHas('memberships', [
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'role' => MembershipRole::OWNER->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'actor_user_id' => $user->id,
            'event' => 'organization.created',
            'auditable_id' => $organizationId,
        ]);
    }

    public function test_index_returns_only_organizations_the_user_belongs_to(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $myOrganization = Organization::factory()->create(['owner_user_id' => $user->id]);
        $otherOrganization = Organization::factory()->create(['owner_user_id' => $otherUser->id]);

        Membership::factory()->create([
            'organization_id' => $myOrganization->id,
            'user_id' => $user->id,
            'role' => MembershipRole::OWNER,
        ]);

        Membership::factory()->create([
            'organization_id' => $otherOrganization->id,
            'user_id' => $otherUser->id,
            'role' => MembershipRole::OWNER,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/organizations');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($myOrganization->id));
        $this->assertFalse($ids->contains($otherOrganization->id));
    }

    public function test_viewer_cannot_update_organization(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $organization = Organization::factory()->create([
            'owner_user_id' => $owner->id,
            'status' => 'active',
        ]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $viewer->id,
            'role' => MembershipRole::VIEWER,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->putJson('/api/v1/organizations/'.$organization->id, [
            'name' => 'Blocked Rename',
        ]);

        $response->assertForbidden();
    }
}
