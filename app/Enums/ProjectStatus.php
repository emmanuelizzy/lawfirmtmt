<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ON_HOLD = 'ON_HOLD';
    case COMPLETED = 'COMPLETED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE    => 'Active',
            self::ON_HOLD   => 'On Hold',
            self::COMPLETED => 'Completed',
            self::ARCHIVED  => 'Archived',
        };
    }
}
