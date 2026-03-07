<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\DealSpace;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\ShareLink;
use App\Policies\AuditLogPolicy;
use App\Policies\DealSpacePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\FolderPolicy;
use App\Policies\MembershipPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ShareLinkPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Membership::class => MembershipPolicy::class,
        DealSpace::class => DealSpacePolicy::class,
        Folder::class => FolderPolicy::class,
        Document::class => DocumentPolicy::class,
        ShareLink::class => ShareLinkPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
