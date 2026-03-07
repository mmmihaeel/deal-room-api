<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DealSpace;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuthorizationService;

class DealSpacePolicy
{
    public function __construct(private readonly AuthorizationService $authorizationService) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, DealSpace $dealSpace): bool
    {
        return $this->authorizationService->canViewDealSpace($user, $dealSpace);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $this->authorizationService->canManageOrganization($user, $organization);
    }

    public function update(User $user, DealSpace $dealSpace): bool
    {
        return $this->authorizationService->canManageDealSpace($user, $dealSpace);
    }

    public function delete(User $user, DealSpace $dealSpace): bool
    {
        return $this->authorizationService->canManageDealSpace($user, $dealSpace);
    }

    public function managePermissions(User $user, DealSpace $dealSpace): bool
    {
        return $this->authorizationService->canManageDealSpace($user, $dealSpace);
    }
}
