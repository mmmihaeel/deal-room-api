<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DealPermission;
use App\Enums\MembershipRole;
use App\Models\DealSpace;
use App\Models\DealSpacePermission;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['owner_user_id' => $user->id]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => MembershipRole::OWNER,
        ]);

        $service = app(AuthorizationService::class);

        $this->assertTrue($service->canManageOrganization($user, $organization));
    }

    public function test_deal_permission_grant_enables_document_management_for_viewer(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $organization = Organization::factory()->create(['owner_user_id' => $owner->id]);
        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
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
            'permission' => DealPermission::UPLOAD,
            'created_by_user_id' => $owner->id,
        ]);

        $service = app(AuthorizationService::class);

        $this->assertTrue($service->canManageDocuments($viewer, $dealSpace));
    }
}
