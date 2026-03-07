<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealSpace\UpsertDealSpacePermissionsRequest;
use App\Http\Resources\DealSpacePermissionResource;
use App\Models\DealSpace;
use App\Models\DealSpacePermission;
use App\Models\Membership;
use App\Services\AuditLogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class DealSpacePermissionController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(DealSpace $dealSpace): AnonymousResourceCollection
    {
        $this->authorize('managePermissions', $dealSpace);

        $permissions = DealSpacePermission::query()
            ->where('deal_space_id', $dealSpace->id)
            ->with('user')
            ->orderBy('user_id')
            ->orderBy('permission')
            ->paginate(50);

        return DealSpacePermissionResource::collection($permissions);
    }

    public function upsert(UpsertDealSpacePermissionsRequest $request, DealSpace $dealSpace): AnonymousResourceCollection
    {
        $this->authorize('managePermissions', $dealSpace);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $dealSpace, $request) {
            foreach ($validated['grants'] as $grant) {
                $isOrganizationMember = Membership::query()
                    ->where('organization_id', $dealSpace->organization_id)
                    ->where('user_id', (int) $grant['user_id'])
                    ->exists();

                if (! $isOrganizationMember) {
                    abort(422, sprintf('User %s must belong to the deal-space organization.', $grant['user_id']));
                }

                DealSpacePermission::query()
                    ->where('deal_space_id', $dealSpace->id)
                    ->where('user_id', (int) $grant['user_id'])
                    ->delete();

                foreach ($grant['permissions'] as $permission) {
                    DealSpacePermission::query()->create([
                        'deal_space_id' => $dealSpace->id,
                        'user_id' => (int) $grant['user_id'],
                        'permission' => $permission,
                        'created_by_user_id' => $request->user()->id,
                    ]);
                }
            }
        });

        $this->auditLogService->record(
            event: 'deal-space.permissions.updated',
            actor: $request->user(),
            organization: $dealSpace->organization,
            auditable: $dealSpace,
            context: ['grants' => $validated['grants']],
            request: $request,
        );

        $permissions = DealSpacePermission::query()
            ->where('deal_space_id', $dealSpace->id)
            ->with('user')
            ->orderBy('user_id')
            ->orderBy('permission')
            ->paginate(50);

        return DealSpacePermissionResource::collection($permissions);
    }
}
