<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ShareLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShareLink */
class ShareLinkResolveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'share_link' => [
                'id' => $this->id,
                'document_id' => $this->document_id,
                'expires_at' => $this->expires_at,
                'max_downloads' => $this->max_downloads,
                'download_count' => $this->download_count,
                'last_accessed_at' => $this->last_accessed_at,
            ],
            'document' => new DocumentResource($this->whenLoaded('document')),
        ];
    }
}
