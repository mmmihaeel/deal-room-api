<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealSpace\IndexDealSpaceRequest;
use App\Http\Requests\DealSpace\StoreDealSpaceRequest;
use App\Http\Requests\DealSpace\UpdateDealSpaceRequest;
use App\Http\Resources\DealSpaceResource;
use App\Models\DealSpace;
use App\Models\Organization;
use App\Services\AuditLogService;
use App\Services\CacheVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DealSpaceController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CacheVersionService $cacheVersionService,
    ) {}

    public function index(IndexDealSpaceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $payload = $this->cacheVersionService->remember(
            domain: 'deal-space-list',
            scope: $user->id,
            params: $validated,
            seconds: 60,
            resolver: function () use ($validated, $user) {
                $query = DealSpace::query()
                    ->whereHas('organization.memberships', fn ($builder) => $builder->where('user_id', $user->id))
                    ->withCount(['folders', 'documents']);

                if (! empty($validated['organization_id'])) {
                    $query->where('organization_id', (int) $validated['organization_id']);
                }

                if (! empty($validated['status'])) {
                    $query->where('status', $validated['status']);
                }

                if (! empty($validated['search'])) {
                    $search = $validated['search'];
                    $query->where(function ($builder) use ($search) {
                        $builder->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('external_reference', 'like', "%{$search}%");
                    });
                }

                $sort = $validated['sort'] ?? 'created_at';
                $direction = $validated['direction'] ?? 'desc';
                $perPage = (int) ($validated['per_page'] ?? 15);

                $paginator = $query
                    ->orderBy($sort, $direction)
                    ->paginate($perPage)
                    ->withQueryString();

                return DealSpaceResource::collection($paginator)->response()->getData(true);
            }
        );

        return response()->json($payload);
    }

    public function store(StoreDealSpaceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $organization = Organization::query()->findOrFail((int) $validated['organization_id']);

        $this->authorize('create', [DealSpace::class, $organization]);

        $dealSpace = DealSpace::query()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
            'name' => $validated['name'],
            'external_reference' => $validated['external_reference'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'draft',
        ]);

        $this->auditLogService->record(
            event: 'deal-space.created',
            actor: $request->user(),
            organization: $organization,
            auditable: $dealSpace,
            context: $validated,
            request: $request,
        );

        $this->bumpCaches($request->user()->id, $dealSpace->id);

        return (new DealSpaceResource($dealSpace->loadCount(['folders', 'documents'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(DealSpace $dealSpace): DealSpaceResource
    {
        $this->authorize('view', $dealSpace);

        $cachedDealSpace = $this->cacheVersionService->remember(
            domain: 'deal-space-show',
            scope: $dealSpace->id,
            params: ['updated_at' => (string) $dealSpace->updated_at],
            seconds: 120,
            resolver: fn () => DealSpace::query()->withCount(['folders', 'documents'])->findOrFail($dealSpace->id),
        );

        return new DealSpaceResource($cachedDealSpace);
    }

    public function update(UpdateDealSpaceRequest $request, DealSpace $dealSpace): DealSpaceResource
    {
        $this->authorize('update', $dealSpace);

        $validated = $request->validated();

        $dealSpace->fill($validated)->save();

        $this->auditLogService->record(
            event: 'deal-space.updated',
            actor: $request->user(),
            organization: $dealSpace->organization,
            auditable: $dealSpace,
            context: $validated,
            request: $request,
        );

        $this->bumpCaches($request->user()->id, $dealSpace->id);

        return new DealSpaceResource($dealSpace->refresh()->loadCount(['folders', 'documents']));
    }

    public function destroy(DealSpace $dealSpace, Request $request): Response
    {
        $this->authorize('delete', $dealSpace);

        $this->auditLogService->record(
            event: 'deal-space.deleted',
            actor: $request->user(),
            organization: $dealSpace->organization,
            auditable: $dealSpace,
            context: ['deal_space_id' => $dealSpace->id],
            request: $request,
        );

        $dealSpace->delete();

        $this->bumpCaches($request->user()->id, $dealSpace->id);

        return response()->noContent();
    }

    private function bumpCaches(int $userId, int $dealSpaceId): void
    {
        $this->cacheVersionService->bump('deal-space-list', $userId);
        $this->cacheVersionService->bump('deal-space-show', $dealSpaceId);
        $this->cacheVersionService->bump('folder-list', $userId);
        $this->cacheVersionService->bump('document-list', $userId);
        $this->cacheVersionService->bump('share-link-list', $userId);
    }
}
