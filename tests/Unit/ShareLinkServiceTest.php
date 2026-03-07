<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\MembershipRole;
use App\Models\DealSpace;
use App\Models\Document;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\ShareLink;
use App\Models\User;
use App\Services\ShareLinkService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_resolve_token_updates_download_count(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['owner_user_id' => $user->id]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => MembershipRole::OWNER,
        ]);

        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $user->id,
        ]);

        $document = Document::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'owner_user_id' => $user->id,
        ]);

        /** @var ShareLinkService $service */
        $service = app(ShareLinkService::class);

        [$shareLink, $token] = $service->create(
            document: $document,
            actor: $user,
            expiresAt: CarbonImmutable::now()->addDay(),
            maxDownloads: 5,
        );

        $this->assertInstanceOf(ShareLink::class, $shareLink);

        $resolved = $service->resolveToken($token);

        $this->assertNotNull($resolved);
        $this->assertSame($shareLink->id, $resolved->id);
        $this->assertSame(1, $resolved->download_count);
    }

    public function test_resolve_returns_null_when_limit_is_reached(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['owner_user_id' => $user->id]);

        Membership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => MembershipRole::OWNER,
        ]);

        $dealSpace = DealSpace::factory()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $user->id,
        ]);

        $document = Document::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'owner_user_id' => $user->id,
        ]);

        $token = 'limit-reached-token';

        ShareLink::factory()->create([
            'organization_id' => $organization->id,
            'deal_space_id' => $dealSpace->id,
            'document_id' => $document->id,
            'created_by_user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'token_prefix' => 'limit-token',
            'expires_at' => now()->addDay(),
            'max_downloads' => 1,
            'download_count' => 1,
        ]);

        /** @var ShareLinkService $service */
        $service = app(ShareLinkService::class);

        $resolved = $service->resolveToken($token);

        $this->assertNull($resolved);
    }
}
