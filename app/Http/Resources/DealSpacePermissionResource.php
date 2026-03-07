<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DealSpacePermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DealSpacePermission */
class DealSpacePermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deal_space_id' => $this->deal_space_id,
            'user_id' => $this->user_id,
            'permission' => $this->permission?->value,
            'created_by_user_id' => $this->created_by_user_id,
            'created_at' => $this->created_at,
        ];
    }
}
