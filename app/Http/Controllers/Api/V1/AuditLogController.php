<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLog\IndexAuditLogRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Membership;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function index(IndexAuditLogRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $accessibleOrganizationIds = Membership::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('role', [MembershipRole::OWNER->value, MembershipRole::ADMIN->value])
            ->pluck('organization_id');

        if ($accessibleOrganizationIds->isEmpty()) {
            abort(403, 'You are not authorized to view audit logs.');
        }

        if (! empty($validated['organization_id'])) {
            $organization = Organization::query()->findOrFail((int) $validated['organization_id']);
            $this->authorize('viewOrganization', [AuditLog::class, $organization]);
            $accessibleOrganizationIds = collect([$organization->id]);
        }

        $query = AuditLog::query()->whereIn('organization_id', $accessibleOrganizationIds->all());

        if (! empty($validated['actor_user_id'])) {
            $query->where('actor_user_id', (int) $validated['actor_user_id']);
        }

        if (! empty($validated['event'])) {
            $query->where('event', $validated['event']);
        }

        if (! empty($validated['from'])) {
            $query->where('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->where('created_at', '<=', $validated['to']);
        }

        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 25);

        $logs = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return AuditLogResource::collection($logs);
    }
}
