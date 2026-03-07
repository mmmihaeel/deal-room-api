<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Models\AuditLog;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_audit_logs_for_their_organization(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $organization = Organization::factory()->create(['owner_user_id' => $owner->id]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::ADMIN,
        ]);

        AuditLog::factory()->create([
            'organization_id' => $organization->id,
            'actor_user_id' => $owner->id,
            'event' => 'document.created',
            'created_at' => now()->subMinute(),
        ]);

        AuditLog::factory()->create([
            'organization_id' => $organization->id,
            'actor_user_id' => $admin->id,
            'event' => 'share-link.created',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/audit-logs?organization_id='.$organization->id.'&event=share-link.created');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event', 'share-link.created');
    }

    public function test_viewer_cannot_access_audit_logs(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $organization = Organization::factory()->create(['owner_user_id' => $owner->id]);

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

        $response = $this->getJson('/api/v1/audit-logs?organization_id='.$organization->id);

        $response->assertForbidden();
    }

    public function test_admin_cannot_query_audit_logs_for_unrelated_organization(): void
    {
        $admin = User::factory()->create();
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $organizationA = Organization::factory()->create(['owner_user_id' => $ownerA->id]);
        $organizationB = Organization::factory()->create(['owner_user_id' => $ownerB->id]);

        Membership::factory()->create([
            'organization_id' => $organizationA->id,
            'user_id' => $ownerA->id,
            'role' => MembershipRole::OWNER,
        ]);

        Membership::factory()->create([
            'organization_id' => $organizationA->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::ADMIN,
        ]);

        Membership::factory()->create([
            'organization_id' => $organizationB->id,
            'user_id' => $ownerB->id,
            'role' => MembershipRole::OWNER,
        ]);

        AuditLog::factory()->create([
            'organization_id' => $organizationB->id,
            'actor_user_id' => $ownerB->id,
            'event' => 'document.created',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/audit-logs?organization_id='.$organizationB->id);

        $response->assertForbidden();
    }

    public function test_unfiltered_audit_logs_only_include_organizations_where_user_is_owner_or_admin(): void
    {
        $admin = User::factory()->create();
        $owner = User::factory()->create();
        $viewerOrganizationOwner = User::factory()->create();

        $adminOrganization = Organization::factory()->create(['owner_user_id' => $owner->id]);
        $viewerOrganization = Organization::factory()->create(['owner_user_id' => $viewerOrganizationOwner->id]);

        Membership::factory()->create([
            'organization_id' => $adminOrganization->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        Membership::factory()->create([
            'organization_id' => $adminOrganization->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::ADMIN,
        ]);

        Membership::factory()->create([
            'organization_id' => $viewerOrganization->id,
            'user_id' => $viewerOrganizationOwner->id,
            'role' => MembershipRole::OWNER,
        ]);

        Membership::factory()->create([
            'organization_id' => $viewerOrganization->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::VIEWER,
        ]);

        $allowedLog = AuditLog::factory()->create([
            'organization_id' => $adminOrganization->id,
            'actor_user_id' => $owner->id,
            'event' => 'document.created',
        ]);

        $restrictedLog = AuditLog::factory()->create([
            'organization_id' => $viewerOrganization->id,
            'actor_user_id' => $viewerOrganizationOwner->id,
            'event' => 'document.deleted',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/audit-logs');

        $response->assertOk();
        $returnedIds = collect($response->json('data'))->pluck('id');

        $this->assertTrue($returnedIds->contains($allowedLog->id));
        $this->assertFalse($returnedIds->contains($restrictedLog->id));
    }
}
