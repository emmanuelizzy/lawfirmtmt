<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;

test('task status enum has correct labels', function (): void {
    expect(TaskStatus::TODO->label())->toBe('To Do')
        ->and(TaskStatus::IN_PROGRESS->label())->toBe('In Progress')
        ->and(TaskStatus::REVIEW->label())->toBe('Review')
        ->and(TaskStatus::DONE->label())->toBe('Done');
});

test('task priority enum has correct labels', function (): void {
    expect(TaskPriority::LOW->label())->toBe('Low')
        ->and(TaskPriority::MEDIUM->label())->toBe('Medium')
        ->and(TaskPriority::HIGH->label())->toBe('High')
        ->and(TaskPriority::URGENT->label())->toBe('Urgent');
});
