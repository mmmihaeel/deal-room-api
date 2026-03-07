<?php

declare(strict_types=1);

namespace App\Enums;

enum MembershipRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canManageOrganization(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN], true);
    }

    public function canManageMemberships(): bool
    {
        return $this->canManageOrganization();
    }

    public function canManageDeals(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN, self::MEMBER], true);
    }

    public function canManageDocuments(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN, self::MEMBER], true);
    }

    public function canManageShareLinks(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN, self::MEMBER], true);
    }

    public function canViewAuditLogs(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN], true);
    }
}
