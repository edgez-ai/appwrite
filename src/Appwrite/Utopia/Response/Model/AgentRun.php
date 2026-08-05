<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;

class AgentRun extends ProjectResource
{
    public function __construct()
    {
        $this->addResourceRules('Agent Run')
            ->addRule('taskId', [
                'type' => self::TYPE_STRING,
                'description' => 'Task ID.',
                'default' => '',
                'example' => 'task_123',
            ])
            ->addRule('agentType', [
                'type' => self::TYPE_STRING,
                'description' => 'Agent type.',
                'default' => '',
                'example' => 'development_agent',
            ])
            ->addRule('status', [
                'type' => self::TYPE_STRING,
                'description' => 'Run status.',
                'default' => 'queued',
                'example' => 'running',
            ])
            ->addRule('workspaceId', [
                'type' => self::TYPE_STRING,
                'description' => 'Workspace ID.',
                'required' => false,
                'default' => null,
                'example' => 'workspace_123',
            ])
            ->addRule('codexThreadId', [
                'type' => self::TYPE_STRING,
                'description' => 'Codex task ID.',
                'required' => false,
                'default' => null,
                'example' => 'thread_123',
            ])
            ->addRule('attempt', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Attempt number.',
                'default' => 1,
                'example' => 1,
            ])
            ->addRule('inputContextSnapshot', [
                'type' => self::TYPE_JSON,
                'description' => 'Immutable input context snapshot.',
                'default' => [],
                'example' => ['taskId' => 'task_123'],
            ])
            ->addRule('resultSummary', [
                'type' => self::TYPE_STRING,
                'description' => 'Run result summary.',
                'default' => '',
                'example' => 'Build passed.',
            ])
            ->addRule('startedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Run start time.',
                'required' => false,
                'default' => null,
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('completedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Run completion time.',
                'required' => false,
                'default' => null,
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ]);
    }

    public function getName(): string
    {
        return 'Agent Run';
    }

    public function getType(): string
    {
        return Response::MODEL_AGENT_RUN;
    }
}
