<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DealSpace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DealSpace */
class DealSpaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'external_reference' => $this->external_reference,
            'description' => $this->description,
            'status' => $this->status?->value,
            'created_by_user_id' => $this->created_by_user_id,
            'folders_count' => $this->whenCounted('folders'),
            'documents_count' => $this->whenCounted('documents'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
