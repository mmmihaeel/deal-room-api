<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DealSpace;
use App\Models\Document;
use App\Models\User;
use App\Services\AuthorizationService;

class DocumentPolicy
{
    public function __construct(private readonly AuthorizationService $authorizationService) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->authorizationService->canViewDealSpace($user, $document->dealSpace);
    }

    public function create(User $user, DealSpace $dealSpace): bool
    {
        return $this->authorizationService->canManageDocuments($user, $dealSpace);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->authorizationService->canManageDocuments($user, $document->dealSpace);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->authorizationService->canManageDocuments($user, $document->dealSpace);
    }

    public function share(User $user, Document $document): bool
    {
        return $this->authorizationService->canManageShareLinks($user, $document->dealSpace);
    }
}
