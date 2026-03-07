<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        string $event,
        ?User $actor,
        ?Organization $organization,
        ?Model $auditable,
        array $context = [],
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'organization_id' => $organization?->id,
            'actor_user_id' => $actor?->id,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'context' => $context,
            'created_at' => now(),
        ]);
    }
}
