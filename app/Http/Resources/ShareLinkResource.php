<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ShareLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShareLink */
class ShareLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $plainToken = null;
        if ($this->resource instanceof ShareLink) {
            $attributes = $this->resource->getAttributes();
            if (array_key_exists('plain_token', $attributes)) {
                $plainToken = (string) $attributes['plain_token'];
            }
        }

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'deal_space_id' => $this->deal_space_id,
            'document_id' => $this->document_id,
            'created_by_user_id' => $this->created_by_user_id,
            'token_prefix' => $this->token_prefix,
            'token' => $this->when($plainToken !== null, $plainToken),
            'expires_at' => $this->expires_at,
            'max_downloads' => $this->max_downloads,
            'download_count' => $this->download_count,
            'revoked_at' => $this->revoked_at,
            'last_accessed_at' => $this->last_accessed_at,
            'status' => $this->isRevoked() ? 'revoked' : ($this->isExpired() ? 'expired' : 'active'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
