<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'TODO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case REVIEW = 'REVIEW';
    case DONE = 'DONE';

    public function label(): string
    {
        return match ($this) {
            self::TODO        => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::REVIEW      => 'Review',
            self::DONE        => 'Done',
        };
    }
}
