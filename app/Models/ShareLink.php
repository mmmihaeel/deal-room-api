<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'deal_space_id',
        'document_id',
        'created_by_user_id',
        'token_hash',
        'token_prefix',
        'expires_at',
        'max_downloads',
        'download_count',
        'revoked_at',
        'last_accessed_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function dealSpace(): BelongsTo
    {
        return $this->belongsTo(DealSpace::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasReachedLimit(): bool
    {
        return $this->max_downloads !== null && $this->download_count >= $this->max_downloads;
    }
}
