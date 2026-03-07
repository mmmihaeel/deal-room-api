<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\ShareLink;
use App\Models\User;
use App\Services\AuthorizationService;

class ShareLinkPolicy
{
    public function __construct(private readonly AuthorizationService $authorizationService) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, ShareLink $shareLink): bool
    {
        return $this->authorizationService->canViewDealSpace($user, $shareLink->dealSpace);
    }

    public function create(User $user, Document $document): bool
    {
        return $this->authorizationService->canManageShareLinks($user, $document->dealSpace);
    }

    public function delete(User $user, ShareLink $shareLink): bool
    {
        return $this->authorizationService->canManageShareLinks($user, $shareLink->dealSpace);
    }
}
