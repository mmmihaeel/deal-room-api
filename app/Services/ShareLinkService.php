<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\ShareLink;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShareLinkService
{
    public function create(Document $document, User $actor, CarbonImmutable $expiresAt, ?int $maxDownloads): array
    {
        $token = Str::random(64);

        $shareLink = ShareLink::query()->create([
            'organization_id' => $document->organization_id,
            'deal_space_id' => $document->deal_space_id,
            'document_id' => $document->id,
            'created_by_user_id' => $actor->id,
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 12),
            'expires_at' => $expiresAt,
            'max_downloads' => $maxDownloads,
            'download_count' => 0,
        ]);

        return [$shareLink->refresh()->load(['document', 'creator']), $token];
    }

    public function revoke(ShareLink $shareLink): ShareLink
    {
        if ($shareLink->revoked_at === null) {
            $shareLink->forceFill([
                'revoked_at' => now(),
            ])->save();
        }

        return $shareLink;
    }

    public function resolveToken(string $token): ?ShareLink
    {
        $tokenHash = hash('sha256', $token);
        $cacheKey = sprintf('share-link:lookup:%s', $tokenHash);

        $shareLinkId = Cache::remember($cacheKey, 60, function () use ($tokenHash) {
            return ShareLink::query()
                ->where('token_hash', $tokenHash)
                ->value('id');
        });

        if ($shareLinkId === null) {
            return null;
        }

        /** @var ShareLink|null $shareLink */
        $shareLink = DB::transaction(function () use ($shareLinkId) {
            $link = ShareLink::query()
                ->whereKey($shareLinkId)
                ->lockForUpdate()
                ->first();

            if ($link === null || $link->isRevoked() || $link->isExpired() || $link->hasReachedLimit()) {
                return null;
            }

            $link->increment('download_count');
            $link->forceFill([
                'last_accessed_at' => now(),
            ])->save();

            return $link;
        });

        if ($shareLink === null) {
            return null;
        }

        return $shareLink->fresh(['document', 'document.dealSpace', 'document.organization']);
    }
}
