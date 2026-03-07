<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DealPermission;
use App\Enums\DealSpaceStatus;
use App\Enums\MembershipRole;
use App\Models\AuditLog;
use App\Models\DealSpace;
use App\Models\DealSpacePermission;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@acme.test'],
            [
                'name' => 'Ava Carter',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@acme.test'],
            [
                'name' => 'Noah Bennett',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $member = User::query()->updateOrCreate(
            ['email' => 'member@acme.test'],
            [
                'name' => 'Ethan Walsh',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $viewer = User::query()->updateOrCreate(
            ['email' => 'viewer@acme.test'],
            [
                'name' => 'Mia Turner',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $externalOwner = User::query()->updateOrCreate(
            ['email' => 'owner@northwind.test'],
            [
                'name' => 'Lucas Rivera',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $acme = Organization::query()->updateOrCreate(
            ['slug' => 'acme-capital'],
            [
                'owner_user_id' => $owner->id,
                'name' => 'Acme Capital',
                'status' => 'active',
            ]
        );

        $northwind = Organization::query()->updateOrCreate(
            ['slug' => 'northwind-holdings'],
            [
                'owner_user_id' => $externalOwner->id,
                'name' => 'Northwind Holdings',
                'status' => 'active',
            ]
        );

        $this->syncMembership($acme->id, $owner->id, MembershipRole::OWNER);
        $this->syncMembership($acme->id, $admin->id, MembershipRole::ADMIN, $owner->id);
        $this->syncMembership($acme->id, $member->id, MembershipRole::MEMBER, $admin->id);
        $this->syncMembership($acme->id, $viewer->id, MembershipRole::VIEWER, $admin->id);

        $this->syncMembership($northwind->id, $externalOwner->id, MembershipRole::OWNER);
        $this->syncMembership($northwind->id, $admin->id, MembershipRole::MEMBER, $externalOwner->id);

        $seriesA = DealSpace::query()->updateOrCreate(
            ['organization_id' => $acme->id, 'name' => 'Series A - Atlas Bio'],
            [
                'created_by_user_id' => $owner->id,
                'external_reference' => 'DS-ATLAS-A',
                'description' => 'Due diligence room for Atlas Bio Series A process.',
                'status' => DealSpaceStatus::ACTIVE,
            ]
        );

        $mna = DealSpace::query()->updateOrCreate(
            ['organization_id' => $acme->id, 'name' => 'M&A - Orbit Systems'],
            [
                'created_by_user_id' => $admin->id,
                'external_reference' => 'DS-ORBIT-MA',
                'description' => 'Acquisition documentation for Orbit Systems.',
                'status' => DealSpaceStatus::DRAFT,
            ]
        );

        $northwindRoom = DealSpace::query()->updateOrCreate(
            ['organization_id' => $northwind->id, 'name' => 'Debt Restructure 2026'],
            [
                'created_by_user_id' => $externalOwner->id,
                'external_reference' => 'DS-NW-DR26',
                'description' => 'Debt restructuring documents and lender correspondence.',
                'status' => DealSpaceStatus::ACTIVE,
            ]
        );

        $financialsFolder = Folder::query()->updateOrCreate(
            ['deal_space_id' => $seriesA->id, 'name' => 'Financials'],
            [
                'organization_id' => $acme->id,
                'created_by_user_id' => $owner->id,
                'parent_id' => null,
            ]
        );

        $legalFolder = Folder::query()->updateOrCreate(
            ['deal_space_id' => $seriesA->id, 'name' => 'Legal'],
            [
                'organization_id' => $acme->id,
                'created_by_user_id' => $admin->id,
                'parent_id' => null,
            ]
        );

        $operationsFolder = Folder::query()->updateOrCreate(
            ['deal_space_id' => $mna->id, 'name' => 'Operations'],
            [
                'organization_id' => $acme->id,
                'created_by_user_id' => $admin->id,
                'parent_id' => null,
            ]
        );

        $docOne = Document::query()->updateOrCreate(
            ['deal_space_id' => $seriesA->id, 'filename' => 'q4-financial-model.xlsx'],
            [
                'organization_id' => $acme->id,
                'folder_id' => $financialsFolder->id,
                'owner_user_id' => $owner->id,
                'title' => 'Q4 Financial Model',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'size_bytes' => 182456,
                'version' => 3,
                'checksum' => hash('sha256', 'q4-financial-model'),
                'metadata' => ['quarter' => 'Q4', 'fiscal_year' => 2025],
                'uploaded_at' => now()->subDays(10),
            ]
        );

        $docTwo = Document::query()->updateOrCreate(
            ['deal_space_id' => $seriesA->id, 'filename' => 'founders-agreement.pdf'],
            [
                'organization_id' => $acme->id,
                'folder_id' => $legalFolder->id,
                'owner_user_id' => $admin->id,
                'title' => 'Founders Agreement',
                'mime_type' => 'application/pdf',
                'size_bytes' => 83425,
                'version' => 2,
                'checksum' => hash('sha256', 'founders-agreement'),
                'metadata' => ['document_type' => 'contract'],
                'uploaded_at' => now()->subDays(8),
            ]
        );

        $docThree = Document::query()->updateOrCreate(
            ['deal_space_id' => $mna->id, 'filename' => 'ops-transition-plan.docx'],
            [
                'organization_id' => $acme->id,
                'folder_id' => $operationsFolder->id,
                'owner_user_id' => $member->id,
                'title' => 'Operations Transition Plan',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'size_bytes' => 257910,
                'version' => 1,
                'checksum' => hash('sha256', 'ops-transition-plan'),
                'metadata' => ['workstream' => 'operations'],
                'uploaded_at' => now()->subDays(4),
            ]
        );

        $this->upsertPermission($seriesA->id, $viewer->id, DealPermission::VIEW, $admin->id);
        $this->upsertPermission($seriesA->id, $member->id, DealPermission::UPLOAD, $admin->id);
        $this->upsertPermission($seriesA->id, $member->id, DealPermission::SHARE, $admin->id);

        ShareLink::query()->updateOrCreate(
            ['document_id' => $docOne->id, 'token_prefix' => 'atlasshr01'],
            [
                'organization_id' => $acme->id,
                'deal_space_id' => $seriesA->id,
                'created_by_user_id' => $admin->id,
                'token_hash' => hash('sha256', 'atlas-share-token-1'),
                'expires_at' => now()->addDays(7),
                'max_downloads' => 15,
                'download_count' => 3,
                'revoked_at' => null,
                'last_accessed_at' => now()->subHours(6),
            ]
        );

        ShareLink::query()->updateOrCreate(
            ['document_id' => $docTwo->id, 'token_prefix' => 'atlasshr02'],
            [
                'organization_id' => $acme->id,
                'deal_space_id' => $seriesA->id,
                'created_by_user_id' => $owner->id,
                'token_hash' => hash('sha256', 'atlas-share-token-2'),
                'expires_at' => now()->subDay(),
                'max_downloads' => 3,
                'download_count' => 3,
                'revoked_at' => null,
                'last_accessed_at' => now()->subDay(),
            ]
        );

        AuditLog::query()->create([
            'organization_id' => $acme->id,
            'actor_user_id' => $owner->id,
            'event' => 'organization.seeded',
            'auditable_type' => Organization::class,
            'auditable_id' => $acme->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'demo-seeder',
            'context' => ['seed' => 'demo'],
            'created_at' => now()->subMinutes(30),
        ]);

        AuditLog::query()->create([
            'organization_id' => $acme->id,
            'actor_user_id' => $admin->id,
            'event' => 'document.seeded',
            'auditable_type' => Document::class,
            'auditable_id' => $docThree->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'demo-seeder',
            'context' => ['seed' => 'demo', 'document' => $docThree->title],
            'created_at' => now()->subMinutes(20),
        ]);

        AuditLog::query()->create([
            'organization_id' => $northwind->id,
            'actor_user_id' => $externalOwner->id,
            'event' => 'deal-space.seeded',
            'auditable_type' => DealSpace::class,
            'auditable_id' => $northwindRoom->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'demo-seeder',
            'context' => ['seed' => 'demo'],
            'created_at' => now()->subMinutes(10),
        ]);
    }

    private function syncMembership(int $organizationId, int $userId, MembershipRole $role, ?int $invitedBy = null): void
    {
        Membership::query()->updateOrCreate(
            [
                'organization_id' => $organizationId,
                'user_id' => $userId,
            ],
            [
                'role' => $role,
                'invited_by_user_id' => $invitedBy,
                'joined_at' => now(),
            ]
        );
    }

    private function upsertPermission(int $dealSpaceId, int $userId, DealPermission $permission, int $createdBy): void
    {
        DealSpacePermission::query()->updateOrCreate(
            [
                'deal_space_id' => $dealSpaceId,
                'user_id' => $userId,
                'permission' => $permission,
            ],
            [
                'created_by_user_id' => $createdBy,
            ]
        );
    }
}
