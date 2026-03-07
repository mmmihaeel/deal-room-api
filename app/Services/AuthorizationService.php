<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DealPermission;
use App\Enums\MembershipRole;
use App\Models\DealSpace;
use App\Models\DealSpacePermission;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;

class AuthorizationService
{
    public function getMembership(User $user, Organization|int $organization): ?Membership
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        return Membership::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->first();
    }

    public function hasOrganizationRole(User $user, Organization|int $organization, array $roles): bool
    {
        $membership = $this->getMembership($user, $organization);

        if ($membership === null) {
            return false;
        }

        return in_array($membership->role, $roles, true);
    }

    public function canViewOrganization(User $user, Organization $organization): bool
    {
        return $this->getMembership($user, $organization) !== null;
    }

    public function canManageOrganization(User $user, Organization $organization): bool
    {
        return $this->hasOrganizationRole($user, $organization, [MembershipRole::OWNER, MembershipRole::ADMIN]);
    }

    public function canDeleteOrganization(User $user, Organization $organization): bool
    {
        return $this->hasOrganizationRole($user, $organization, [MembershipRole::OWNER]);
    }

    public function canManageMemberships(User $user, Organization $organization): bool
    {
        return $this->hasOrganizationRole($user, $organization, [MembershipRole::OWNER, MembershipRole::ADMIN]);
    }

    public function canViewDealSpace(User $user, DealSpace $dealSpace): bool
    {
        return $this->canViewOrganization($user, $dealSpace->organization);
    }

    public function canManageDealSpace(User $user, DealSpace $dealSpace): bool
    {
        return $this->canManageOrganization($user, $dealSpace->organization)
            || $this->hasDealPermission($user, $dealSpace, DealPermission::MANAGE);
    }

    public function canManageDocuments(User $user, DealSpace $dealSpace): bool
    {
        $membership = $this->getMembership($user, $dealSpace->organization);

        if ($membership === null) {
            return false;
        }

        if ($membership->role->canManageDocuments()) {
            return true;
        }

        return $this->hasDealPermission($user, $dealSpace, DealPermission::UPLOAD)
            || $this->hasDealPermission($user, $dealSpace, DealPermission::MANAGE);
    }

    public function canManageShareLinks(User $user, DealSpace $dealSpace): bool
    {
        $membership = $this->getMembership($user, $dealSpace->organization);

        if ($membership === null) {
            return false;
        }

        if ($membership->role->canManageShareLinks()) {
            return true;
        }

        return $this->hasDealPermission($user, $dealSpace, DealPermission::SHARE)
            || $this->hasDealPermission($user, $dealSpace, DealPermission::MANAGE);
    }

    public function canViewAuditLogs(User $user, Organization $organization): bool
    {
        return $this->hasOrganizationRole($user, $organization, [MembershipRole::OWNER, MembershipRole::ADMIN]);
    }

    public function hasDealPermission(User $user, DealSpace $dealSpace, DealPermission $permission): bool
    {
        return DealSpacePermission::query()
            ->where('deal_space_id', $dealSpace->id)
            ->where('user_id', $user->id)
            ->where('permission', $permission->value)
            ->exists();
    }
}
