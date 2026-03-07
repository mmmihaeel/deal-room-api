<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuthorizationService;

class MembershipPolicy
{
    public function __construct(private readonly AuthorizationService $authorizationService) {}

    public function viewAny(User $user, Organization $organization): bool
    {
        return $this->authorizationService->canManageMemberships($user, $organization)
            || $this->authorizationService->canViewAuditLogs($user, $organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $this->authorizationService->canManageMemberships($user, $organization);
    }

    public function update(User $user, Membership $membership): bool
    {
        return $this->authorizationService->canManageMemberships($user, $membership->organization);
    }

    public function delete(User $user, Membership $membership): bool
    {
        return $this->authorizationService->canManageMemberships($user, $membership->organization)
            && $membership->role->value !== 'owner';
    }
}
