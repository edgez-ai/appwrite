<?php

namespace Appwrite\ProjectManagement;

class TaskState
{
    public const array STATUSES = ['draft', 'ready', 'in_progress', 'blocked', 'review', 'done', 'cancelled'];

    private const array TRANSITIONS = [
        'draft' => ['ready', 'cancelled'],
        'ready' => ['in_progress', 'draft', 'cancelled'],
        'in_progress' => ['blocked', 'review', 'cancelled'],
        'blocked' => ['ready', 'in_progress', 'cancelled'],
        'review' => ['in_progress', 'done', 'blocked', 'cancelled'],
        'done' => ['in_progress'],
        'cancelled' => ['draft'],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return $from === $to || \in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * @return string[]
     */
    public static function next(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }
}
