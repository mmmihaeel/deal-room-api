<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Models\DealSpace;
use App\Models\Folder;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentAndFolderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_folder_and_document_metadata(): void
    {
        $member = User::factory()->create();
        $organization = Organization::factory()->create(['owner_user_id' => $member->id]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => MembershipRole::MEMBER,
        ]);

        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $member->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($member);

        $folderResponse = $this->postJson('/api/v1/folders', [
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'name' => 'Due Diligence',
        ]);

        $folderResponse->assertCreated();
        $folderId = (int) $folderResponse->json('data.id');

        $documentResponse = $this->postJson('/api/v1/documents', [
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'folder_id' => $folderId,
            'title' => 'Cap Table',
            'filename' => 'cap-table.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size_bytes' => 10240,
            'metadata' => ['quarter' => 'Q4'],
        ]);

        $documentResponse
            ->assertCreated()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.title', 'Cap Table');
    }

    public function test_document_creation_requires_valid_payload(): void
    {
        $member = User::factory()->create();
        $organization = Organization::factory()->create(['owner_user_id' => $member->id]);
        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => MembershipRole::MEMBER,
        ]);
        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $member->id,
        ]);

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/documents', [
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'filename' => 'missing-title.pdf',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'mime_type', 'size_bytes']);
    }

    public function test_viewer_cannot_create_document(): void
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();

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

        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->postJson('/api/v1/documents', [
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'title' => 'Confidential Deck',
            'filename' => 'deck.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
        ]);

        $response->assertForbidden();
    }

    public function test_document_creation_rejects_mismatched_organization_and_deal_space(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $organizationA = Organization::factory()->create(['owner_user_id' => $owner->id]);
        $organizationB = Organization::factory()->create(['owner_user_id' => $member->id]);

        Membership::factory()->create([
            'organization_id' => $organizationA->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        Membership::factory()->create([
            'organization_id' => $organizationA->id,
            'user_id' => $member->id,
            'role' => MembershipRole::MEMBER,
        ]);

        Membership::factory()->create([
            'organization_id' => $organizationB->id,
            'user_id' => $member->id,
            'role' => MembershipRole::OWNER,
        ]);

        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organizationA->id,
            'created_by_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/documents', [
            'organization_id' => $organizationB->id,
            'deal_space_id' => $dealSpace->id,
            'title' => 'Invalid Scope Document',
            'filename' => 'invalid-scope.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'The selected organization does not own the given deal space.');
    }

    public function test_document_creation_rejects_folder_from_different_deal_space(): void
    {
        $owner = User::factory()->create();

        $organization = Organization::factory()->create(['owner_user_id' => $owner->id]);
        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::OWNER,
        ]);

        $dealSpaceA = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
        ]);

        $dealSpaceB = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
        ]);

        $foreignFolder = Folder::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpaceB->id,
            'created_by_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/documents', [
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpaceA->id,
            'folder_id' => $foreignFolder->id,
            'title' => 'Folder Boundary Test',
            'filename' => 'folder-boundary.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 4096,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Folder must belong to the same deal space.');
    }
}
