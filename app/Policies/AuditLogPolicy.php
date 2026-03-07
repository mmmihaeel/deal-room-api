<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuthorizationService;

class AuditLogPolicy
{
    public function __construct(private readonly AuthorizationService $authorizationService) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function viewOrganization(User $user, Organization $organization): bool
    {
        return $this->authorizationService->canViewAuditLogs($user, $organization);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($auditLog->organization === null) {
            return false;
        }

        return $this->authorizationService->canViewAuditLogs($user, $auditLog->organization);
    }
}
