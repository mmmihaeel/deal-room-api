<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\IndexOrganizationRequest;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Membership;
use App\Models\Organization;
use App\Services\AuditLogService;
use App\Services\CacheVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CacheVersionService $cacheVersionService,
    ) {}

    public function index(IndexOrganizationRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $user = $request->user();

        $query = Organization::query()
            ->whereHas('memberships', fn ($builder) => $builder->where('user_id', $user->id))
            ->with(['memberships' => fn ($builder) => $builder->where('user_id', $user->id)]);

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 15);

        $organizations = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $this->authorize('create', Organization::class);

        $user = $request->user();

        $organization = DB::transaction(function () use ($request, $user) {
            $organization = Organization::query()->create([
                'owner_user_id' => $user->id,
                'name' => $request->validated('name'),
                'slug' => $this->generateUniqueSlug($request->validated('name')),
                'status' => 'active',
            ]);

            Membership::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $organization;
        });

        $this->auditLogService->record(
            event: 'organization.created',
            actor: $user,
            organization: $organization,
            auditable: $organization,
            context: ['name' => $organization->name],
            request: $request,
        );

        $this->cacheVersionService->bump('organization-list', $user->id);

        return (new OrganizationResource($organization->load(['memberships' => fn ($builder) => $builder->where('user_id', $user->id)])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Organization $organization): OrganizationResource
    {
        $this->authorize('view', $organization);

        $cachedOrganization = $this->cacheVersionService->remember(
            domain: 'organization-show',
            scope: $organization->id,
            params: ['updated_at' => (string) $organization->updated_at],
            seconds: 120,
            resolver: fn () => Organization::query()->with('owner')->findOrFail($organization->id),
        );

        return new OrganizationResource($cachedOrganization->load(['memberships' => fn ($builder) => $builder->where('user_id', auth()->id())]));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $this->authorize('update', $organization);

        $validated = $request->validated();
        if (array_key_exists('name', $validated)) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name'], $organization->id);
        }

        $organization->fill($validated)->save();

        $this->auditLogService->record(
            event: 'organization.updated',
            actor: $request->user(),
            organization: $organization,
            auditable: $organization,
            context: $validated,
            request: $request,
        );

        $this->bumpOrganizationCaches($organization);

        return new OrganizationResource($organization->refresh()->load(['memberships' => fn ($builder) => $builder->where('user_id', $request->user()->id)]));
    }

    public function destroy(Organization $organization, \Illuminate\Http\Request $request): Response
    {
        $this->authorize('delete', $organization);

        $this->auditLogService->record(
            event: 'organization.deleted',
            actor: $request->user(),
            organization: $organization,
            auditable: $organization,
            context: ['organization_id' => $organization->id],
            request: $request,
        );

        $organization->delete();

        $this->bumpOrganizationCaches($organization);

        return response()->noContent();
    }

    private function generateUniqueSlug(string $name, ?int $ignoreOrganizationId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'organization';
        }

        $candidate = $base;
        $suffix = 1;

        while (
            Organization::query()
                ->when($ignoreOrganizationId !== null, fn ($builder) => $builder->where('id', '!=', $ignoreOrganizationId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = sprintf('%s-%s', $base, $suffix);
            $suffix++;
        }

        return $candidate;
    }

    private function bumpOrganizationCaches(Organization $organization): void
    {
        $memberIds = Membership::query()
            ->where('organization_id', $organization->id)
            ->pluck('user_id')
            ->all();

        foreach ($memberIds as $memberId) {
            $this->cacheVersionService->bump('organization-list', (int) $memberId);
            $this->cacheVersionService->bump('deal-space-list', (int) $memberId);
            $this->cacheVersionService->bump('document-list', (int) $memberId);
            $this->cacheVersionService->bump('folder-list', (int) $memberId);
            $this->cacheVersionService->bump('share-link-list', (int) $memberId);
            $this->cacheVersionService->bump('audit-log-list', (int) $memberId);
        }

        $this->cacheVersionService->bump('organization-show', $organization->id);
    }
}
