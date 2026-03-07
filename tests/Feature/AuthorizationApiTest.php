<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DealPermission;
use App\Enums\MembershipRole;
use App\Models\DealSpace;
use App\Models\DealSpacePermission;
use App\Models\Document;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_organization_without_membership(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $organization = Organization::factory()->create(['owner_user_id' => $owner->id]);
        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        Sanctum::actingAs($outsider);

        $this->getJson('/api/v1/organizations/'.$organization->id)
            ->assertForbidden();
    }

    public function test_member_cannot_manage_memberships(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $newUser = User::factory()->create();

        $organization = Organization::factory()->create(['owner_user_id' => $owner->id]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => MembershipRole::MEMBER,
        ]);

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/memberships', [
            'organization_id' => $organization->id,
            'user_id' => $newUser->id,
            'role' => MembershipRole::VIEWER->value,
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_with_share_grant_can_create_share_link(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $organization = Organization::factory()->create(['owner_user_id' => $owner->id]);
        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
            'status' => 'active',
        ]);

        $document = Document::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'owner_user_id' => $owner->id,
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

        DealSpacePermission::factory()->create([
            'deal_space_id' => $dealSpace->id,
            'user_id' => $viewer->id,
            'permission' => DealPermission::SHARE,
            'created_by_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->postJson('/api/v1/share-links', [
            'document_id' => $document->id,
            'expires_at' => now()->addHour()->toIso8601String(),
            'max_downloads' => 5,
        ]);

        $response->assertCreated();
    }

    public function test_member_cannot_create_share_link_for_document_outside_organization_boundary(): void
    {
        $member = User::factory()->create();
        $owner = User::factory()->create();

        $memberOrganization = Organization::factory()->create(['owner_user_id' => $member->id]);
        Membership::factory()->create([
            'organization_id' => $memberOrganization->id,
            'user_id' => $member->id,
            'role' => MembershipRole::MEMBER,
        ]);

        $targetOrganization = Organization::factory()->create(['owner_user_id' => $owner->id]);
        Membership::factory()->create([
            'organization_id' => $targetOrganization->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        $targetDealSpace = DealSpace::factory()->create([
            'organization_id' => $targetOrganization->id,
            'created_by_user_id' => $owner->id,
        ]);

        $targetDocument = Document::factory()->create([
            'organization_id' => $targetOrganization->id,
            'deal_space_id' => $targetDealSpace->id,
            'owner_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/share-links', [
            'document_id' => $targetDocument->id,
            'expires_at' => now()->addHour()->toIso8601String(),
        ]);

        $response->assertForbidden();
    }
}
