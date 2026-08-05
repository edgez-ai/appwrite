<?php

namespace Appwrite\ProjectManagement;

class AgentRunState
{
    public const array STATUSES = ['queued', 'running', 'succeeded', 'failed', 'cancelled'];

    private const array TRANSITIONS = [
        'queued' => ['running', 'cancelled'],
        'running' => ['succeeded', 'failed', 'cancelled'],
        'failed' => ['queued'],
        'succeeded' => [],
        'cancelled' => ['queued'],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return $from === $to || \in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
