<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DealSpace;
use App\Models\Folder;
use App\Models\User;
use App\Services\AuthorizationService;

class FolderPolicy
{
    public function __construct(private readonly AuthorizationService $authorizationService) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Folder $folder): bool
    {
        return $this->authorizationService->canViewDealSpace($user, $folder->dealSpace);
    }

    public function create(User $user, DealSpace $dealSpace): bool
    {
        return $this->authorizationService->canManageDocuments($user, $dealSpace);
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->authorizationService->canManageDocuments($user, $folder->dealSpace);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->authorizationService->canManageDocuments($user, $folder->dealSpace);
    }
}
