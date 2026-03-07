<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareLink\IndexShareLinkRequest;
use App\Http\Requests\ShareLink\StoreShareLinkRequest;
use App\Http\Resources\ShareLinkResolveResource;
use App\Http\Resources\ShareLinkResource;
use App\Models\Document;
use App\Models\ShareLink;
use App\Services\AuditLogService;
use App\Services\CacheVersionService;
use App\Services\ShareLinkService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ShareLinkController extends Controller
{
    public function __construct(
        private readonly ShareLinkService $shareLinkService,
        private readonly AuditLogService $auditLogService,
        private readonly CacheVersionService $cacheVersionService,
    ) {}

    public function index(IndexShareLinkRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $query = ShareLink::query()
            ->whereHas('organization.memberships', fn ($builder) => $builder->where('user_id', $request->user()->id));

        if (! empty($validated['organization_id'])) {
            $query->where('organization_id', (int) $validated['organization_id']);
        }

        if (! empty($validated['deal_space_id'])) {
            $query->where('deal_space_id', (int) $validated['deal_space_id']);
        }

        if (! empty($validated['document_id'])) {
            $query->where('document_id', (int) $validated['document_id']);
        }

        if (! empty($validated['status'])) {
            $query->when($validated['status'] === 'active', function ($builder) {
                $builder->whereNull('revoked_at')->where('expires_at', '>', now());
            })->when($validated['status'] === 'expired', function ($builder) {
                $builder->whereNull('revoked_at')->where('expires_at', '<=', now());
            })->when($validated['status'] === 'revoked', function ($builder) {
                $builder->whereNotNull('revoked_at');
            });
        }

        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 15);

        $links = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return ShareLinkResource::collection($links);
    }

    public function store(StoreShareLinkRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $document = Document::query()->findOrFail((int) $validated['document_id']);
        $this->authorize('create', [ShareLink::class, $document]);

        [$shareLink, $token] = $this->shareLinkService->create(
            $document,
            $request->user(),
            CarbonImmutable::parse($validated['expires_at']),
            isset($validated['max_downloads']) ? (int) $validated['max_downloads'] : null,
        );

        $shareLink->setAttribute('plain_token', $token);

        $this->auditLogService->record(
            event: 'share-link.created',
            actor: $request->user(),
            organization: $document->organization,
            auditable: $shareLink,
            context: [
                'document_id' => $document->id,
                'expires_at' => $shareLink->expires_at,
                'max_downloads' => $shareLink->max_downloads,
            ],
            request: $request,
        );

        $this->cacheVersionService->bump('share-link-list', $request->user()->id);

        return (new ShareLinkResource($shareLink))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(ShareLink $shareLink, Request $request): Response
    {
        $this->authorize('delete', $shareLink);

        $this->shareLinkService->revoke($shareLink);

        $this->auditLogService->record(
            event: 'share-link.revoked',
            actor: $request->user(),
            organization: $shareLink->organization,
            auditable: $shareLink,
            context: ['share_link_id' => $shareLink->id],
            request: $request,
        );

        $this->cacheVersionService->bump('share-link-list', $request->user()->id);

        return response()->noContent();
    }

    public function resolve(string $token, Request $request): ShareLinkResolveResource|JsonResponse
    {
        $shareLink = $this->shareLinkService->resolveToken($token);

        if ($shareLink === null) {
            return response()->json([
                'message' => 'Share link is invalid or expired.',
            ], 404);
        }

        $this->auditLogService->record(
            event: 'share-link.resolved',
            actor: null,
            organization: $shareLink->organization,
            auditable: $shareLink,
            context: [
                'document_id' => $shareLink->document_id,
                'download_count' => $shareLink->download_count,
            ],
            request: $request,
        );

        return new ShareLinkResolveResource($shareLink);
    }
}
