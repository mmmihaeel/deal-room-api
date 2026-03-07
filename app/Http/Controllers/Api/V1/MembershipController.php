<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\IndexMembershipRequest;
use App\Http\Requests\Membership\StoreMembershipRequest;
use App\Http\Requests\Membership\UpdateMembershipRequest;
use App\Http\Resources\MembershipResource;
use App\Models\Membership;
use App\Models\Organization;
use App\Services\AuditLogService;
use App\Services\CacheVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MembershipController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CacheVersionService $cacheVersionService,
    ) {}

    public function index(IndexMembershipRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $organization = Organization::query()->findOrFail((int) $validated['organization_id']);
        $this->authorize('viewAny', [Membership::class, $organization]);

        $query = Membership::query()
            ->where('organization_id', $organization->id)
            ->with('user');

        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->whereHas('user', function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 15);

        $memberships = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return MembershipResource::collection($memberships);
    }

    public function store(StoreMembershipRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $organization = Organization::query()->findOrFail((int) $validated['organization_id']);
        $this->authorize('create', [Membership::class, $organization]);

        $alreadyMember = Membership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', (int) $validated['user_id'])
            ->exists();

        if ($alreadyMember) {
            abort(422, 'The user is already a member of the organization.');
        }

        if ($validated['role'] === MembershipRole::OWNER->value) {
            $hasOwner = Membership::query()
                ->where('organization_id', $organization->id)
                ->where('role', MembershipRole::OWNER->value)
                ->exists();

            if ($hasOwner) {
                abort(422, 'Only one owner membership is allowed.');
            }
        }

        $membership = Membership::query()->create([
            'organization_id' => $organization->id,
            'user_id' => (int) $validated['user_id'],
            'role' => $validated['role'],
            'invited_by_user_id' => $request->user()->id,
            'joined_at' => now(),
        ]);

        $this->auditLogService->record(
            event: 'membership.created',
            actor: $request->user(),
            organization: $organization,
            auditable: $membership,
            context: [
                'target_user_id' => $membership->user_id,
                'role' => $membership->role->value,
            ],
            request: $request,
        );

        $this->cacheVersionService->bump('organization-list', $membership->user_id);

        return (new MembershipResource($membership->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateMembershipRequest $request, Membership $membership): MembershipResource
    {
        $this->authorize('update', $membership);

        $validated = $request->validated();

        if (
            $membership->role === MembershipRole::OWNER
            && $validated['role'] !== MembershipRole::OWNER->value
        ) {
            $ownerCount = Membership::query()
                ->where('organization_id', $membership->organization_id)
                ->where('role', MembershipRole::OWNER->value)
                ->count();

            if ($ownerCount <= 1) {
                abort(422, 'Organization must retain at least one owner.');
            }
        }

        $membership->update([
            'role' => $validated['role'],
        ]);

        $this->auditLogService->record(
            event: 'membership.updated',
            actor: $request->user(),
            organization: $membership->organization,
            auditable: $membership,
            context: ['role' => $membership->role->value],
            request: $request,
        );

        $this->cacheVersionService->bump('organization-list', $membership->user_id);

        return new MembershipResource($membership->refresh()->load('user'));
    }

    public function destroy(Membership $membership, Request $request): Response
    {
        $this->authorize('delete', $membership);

        $this->auditLogService->record(
            event: 'membership.deleted',
            actor: $request->user(),
            organization: $membership->organization,
            auditable: $membership,
            context: [
                'target_user_id' => $membership->user_id,
                'role' => $membership->role->value,
            ],
            request: $request,
        );

        $userId = $membership->user_id;
        $membership->delete();

        $this->cacheVersionService->bump('organization-list', $userId);

        return response()->noContent();
    }
}
