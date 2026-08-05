<?php

namespace Appwrite\ProjectManagement;

class Capabilities
{
    public const string SCHEMA_VERSION = '2026-08-06';

    public static function get(): array
    {
        $transitions = [];
        foreach (TaskState::STATUSES as $status) {
            $transitions[$status] = TaskState::next($status);
        }

        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'service' => 'projectManagement',
            'principles' => [
                'Use caller-defined IDs for retry-safe create operations.',
                'Use the task status endpoint with expectedStatus for state transitions.',
                'Use the claim endpoint instead of PATCHing a ready task to in_progress.',
                'Treat Sticker content, context snapshots, summaries, and Evidence as immutable.',
            ],
            'taskStatuses' => TaskState::STATUSES,
            'taskTransitions' => $transitions,
            'stickerStatuses' => ['valid', 'invalid', 'superseded'],
            'stickerTypes' => ['note', 'fact', 'decision', 'requirement', 'risk', 'question', 'constraint', 'result'],
            'contextEndpoints' => [
                'project' => ['method' => 'GET', 'path' => '/v1/project/context'],
                'task' => ['method' => 'GET', 'path' => '/v1/tasks/{taskId}/context'],
            ],
            'retryableErrors' => [
                'project_task_status_conflict' => 'Reload task context, choose a legal next state, and retry.',
                'agent_run_status_conflict' => 'Reload the Agent Run and retry from its current state.',
                'project_resource_already_exists' => 'Generate a new ID or retry with the original create input.',
            ],
            'agentActions' => [
                'claimTask' => [
                    'method' => 'PATCH',
                    'path' => '/v1/tasks/{taskId}/claim',
                    'retry' => 'idempotent_for_same_agent',
                ],
                'transitionTask' => [
                    'method' => 'PATCH',
                    'path' => '/v1/tasks/{taskId}/status',
                    'retry' => 'idempotent_when_expectedStatus_equals_status',
                ],
                'createSticker' => [
                    'method' => 'POST',
                    'path' => '/v1/stickers',
                    'retry' => 'reuse_stickerId',
                ],
                'startAgentRun' => [
                    'method' => 'POST',
                    'path' => '/v1/agent-runs',
                    'retry' => 'reuse_runId',
                ],
                'attachEvidence' => [
                    'method' => 'POST',
                    'path' => '/v1/evidence',
                    'retry' => 'reuse_evidenceId',
                ],
            ],
        ];
    }
}
