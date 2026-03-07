<?php

declare(strict_types=1);

namespace App\Enums;

enum DealPermission: string
{
    case VIEW = 'view';
    case UPLOAD = 'upload';
    case SHARE = 'share';
    case MANAGE = 'manage';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
