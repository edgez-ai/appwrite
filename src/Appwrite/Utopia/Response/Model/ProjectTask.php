<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;

class ProjectTask extends ProjectResource
{
    public function __construct()
    {
        $this->addResourceRules('Task')
            ->addRule('parentTaskId', [
                'type' => self::TYPE_STRING,
                'description' => 'Parent task ID.',
                'required' => false,
                'default' => null,
                'example' => 'task_parent',
            ])
            ->addRule('title', [
                'type' => self::TYPE_STRING,
                'description' => 'Task title.',
                'default' => '',
                'example' => 'Improve BLE startup',
            ])
            ->addRule('description', [
                'type' => self::TYPE_STRING,
                'description' => 'Task description.',
                'default' => '',
                'example' => 'Reduce initial connection latency.',
            ])
            ->addRule('status', [
                'type' => self::TYPE_STRING,
                'description' => 'Task workflow status.',
                'default' => 'draft',
                'example' => 'ready',
            ])
            ->addRule('priority', [
                'type' => self::TYPE_STRING,
                'description' => 'Task priority.',
                'default' => 'medium',
                'example' => 'high',
            ])
            ->addRule('assigneeType', [
                'type' => self::TYPE_STRING,
                'description' => 'Task assignee type.',
                'default' => 'human',
                'example' => 'development_agent',
            ])
            ->addRule('assigneeId', [
                'type' => self::TYPE_STRING,
                'description' => 'Task assignee ID.',
                'required' => false,
                'default' => null,
                'example' => 'agent_development',
            ])
            ->addRule('claimedByType', [
                'type' => self::TYPE_STRING,
                'description' => 'Type of agent holding the task claim.',
                'required' => false,
                'default' => null,
                'example' => 'development_agent',
            ])
            ->addRule('claimedById', [
                'type' => self::TYPE_STRING,
                'description' => 'Stable ID of the agent holding the task claim.',
                'required' => false,
                'default' => null,
                'example' => 'agent_development',
            ])
            ->addRule('claimedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Time the task was atomically claimed.',
                'required' => false,
                'default' => null,
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('agentRunId', [
                'type' => self::TYPE_STRING,
                'description' => 'Agent Run associated with the active claim.',
                'required' => false,
                'default' => null,
                'example' => 'run_123',
            ])
            ->addRule('acceptanceCriteria', [
                'type' => self::TYPE_JSON,
                'description' => 'Acceptance criteria.',
                'default' => [],
                'example' => ['Connection completes within two seconds.'],
            ])
            ->addRule('sortOrder', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Sibling sort order.',
                'default' => 0,
                'example' => 10,
            ])
            ->addRule('createdBy', [
                'type' => self::TYPE_STRING,
                'description' => 'User that created the task.',
                'required' => false,
                'default' => null,
                'example' => 'user_123',
            ]);
    }

    public function getName(): string
    {
        return 'Project Task';
    }

    public function getType(): string
    {
        return Response::MODEL_PROJECT_TASK;
    }
}
