<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DealPermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealSpacePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_space_id',
        'user_id',
        'permission',
        'created_by_user_id',
    ];

    protected $casts = [
        'permission' => DealPermission::class,
    ];

    public function dealSpace(): BelongsTo
    {
        return $this->belongsTo(DealSpace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
