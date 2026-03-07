<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Services\AuthorizationService;

class OrganizationPolicy
{
    public function __construct(private readonly AuthorizationService $authorizationService) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->authorizationService->canViewOrganization($user, $organization);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->authorizationService->canManageOrganization($user, $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->authorizationService->canDeleteOrganization($user, $organization);
    }
}
