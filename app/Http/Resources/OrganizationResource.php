<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $membershipRole = null;
        if ($this->resource instanceof Organization && $this->resource->relationLoaded('memberships')) {
            $membershipRole = $this->resource->memberships
                ->firstWhere('user_id', $request->user()?->id)
                ?->role
                ?->value;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'owner_user_id' => $this->owner_user_id,
            'membership_role' => $membershipRole,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
