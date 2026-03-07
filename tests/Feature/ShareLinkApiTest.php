<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Models\AuditLog;
use App\Models\DealSpace;
use App\Models\Document;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShareLinkApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_link_can_be_created_resolved_and_revoked(): void
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

        $document = Document::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'owner_user_id' => $member->id,
        ]);

        Sanctum::actingAs($member);

        $createResponse = $this->postJson('/api/v1/share-links', [
            'document_id' => $document->id,
            'expires_at' => now()->addDay()->toIso8601String(),
            'max_downloads' => 2,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.document_id', $document->id)
            ->assertJsonStructure(['data' => ['id', 'token']]);

        $token = (string) $createResponse->json('data.token');
        $shareLinkId = (int) $createResponse->json('data.id');

        $resolveResponse = $this->getJson('/api/v1/share-links/'.$token);

        $resolveResponse
            ->assertOk()
            ->assertJsonPath('data.document.id', $document->id)
            ->assertJsonPath('data.share_link.id', $shareLinkId);

        $revokeResponse = $this->deleteJson('/api/v1/share-links/'.$shareLinkId);
        $revokeResponse->assertNoContent();

        $this->getJson('/api/v1/share-links/'.$token)->assertStatus(404);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'actor_user_id' => $member->id,
            'event' => 'share-link.created',
            'auditable_id' => $shareLinkId,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'actor_user_id' => null,
            'event' => 'share-link.resolved',
            'auditable_id' => $shareLinkId,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'actor_user_id' => $member->id,
            'event' => 'share-link.revoked',
            'auditable_id' => $shareLinkId,
        ]);
    }

    public function test_expired_share_link_cannot_be_resolved(): void
    {
        $owner = User::factory()->create();
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
        $document = Document::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'owner_user_id' => $owner->id,
        ]);

        $token = 'expired-public-token';

        ShareLink::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'document_id' => $document->id,
            'created_by_user_id' => $owner->id,
            'token_hash' => hash('sha256', $token),
            'token_prefix' => 'expiredtokn',
            'expires_at' => now()->subHour(),
            'max_downloads' => null,
            'download_count' => 0,
            'revoked_at' => null,
        ]);

        $response = $this->getJson('/api/v1/share-links/'.$token);

        $response->assertStatus(404);
    }

    public function test_revoked_share_link_cannot_be_resolved(): void
    {
        $owner = User::factory()->create();
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

        $document = Document::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'owner_user_id' => $owner->id,
        ]);

        $token = 'revoked-public-token';

        ShareLink::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'document_id' => $document->id,
            'created_by_user_id' => $owner->id,
            'token_hash' => hash('sha256', $token),
            'token_prefix' => 'revokedtokn',
            'expires_at' => now()->addHour(),
            'revoked_at' => now()->subMinute(),
        ]);

        $this->getJson('/api/v1/share-links/'.$token)->assertStatus(404);
    }

    public function test_share_link_resolution_stops_after_max_downloads(): void
    {
        $owner = User::factory()->create();
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

        $document = Document::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'owner_user_id' => $owner->id,
        ]);

        Sanctum::actingAs($owner);

        $createResponse = $this->postJson('/api/v1/share-links', [
            'document_id' => $document->id,
            'expires_at' => now()->addHour()->toIso8601String(),
            'max_downloads' => 1,
        ]);

        $createResponse->assertCreated();

        $shareLinkId = (int) $createResponse->json('data.id');
        $token = (string) $createResponse->json('data.token');

        $this->getJson('/api/v1/share-links/'.$token)
            ->assertOk()
            ->assertJsonPath('data.share_link.download_count', 1);

        $this->getJson('/api/v1/share-links/'.$token)->assertStatus(404);

        $this->assertDatabaseHas('share_links', [
            'id' => $shareLinkId,
            'download_count' => 1,
            'max_downloads' => 1,
        ]);

        $resolvedAuditCount = AuditLog::query()
            ->where('event', 'share-link.resolved')
            ->where('auditable_id', $shareLinkId)
            ->count();

        $this->assertSame(1, $resolvedAuditCount);
    }
}
