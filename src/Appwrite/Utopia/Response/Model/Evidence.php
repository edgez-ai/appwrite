<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;

class Evidence extends ProjectResource
{
    public function __construct()
    {
        $this->addResourceRules('Evidence')
            ->addRule('taskId', [
                'type' => self::TYPE_STRING,
                'description' => 'Task ID.',
                'default' => '',
                'example' => 'task_123',
            ])
            ->addRule('agentRunId', [
                'type' => self::TYPE_STRING,
                'description' => 'Agent Run ID.',
                'default' => '',
                'example' => 'run_123',
            ])
            ->addRule('type', [
                'type' => self::TYPE_STRING,
                'description' => 'Evidence type.',
                'default' => '',
                'example' => 'pull_request',
            ])
            ->addRule('title', [
                'type' => self::TYPE_STRING,
                'description' => 'Evidence title.',
                'required' => false,
                'default' => null,
                'example' => 'BLE optimization PR',
            ])
            ->addRule('url', [
                'type' => self::TYPE_STRING,
                'description' => 'Evidence URL.',
                'required' => false,
                'default' => null,
                'example' => 'https://github.com/example/repo/pull/42',
            ])
            ->addRule('storageFileId', [
                'type' => self::TYPE_STRING,
                'description' => 'Appwrite Storage file ID.',
                'required' => false,
                'default' => null,
                'example' => 'file_123',
            ])
            ->addRule('metadata', [
                'type' => self::TYPE_JSON,
                'description' => 'Evidence metadata.',
                'default' => [],
                'example' => ['commit' => 'abc123'],
            ]);
    }

    public function getName(): string
    {
        return 'Evidence';
    }

    public function getType(): string
    {
        return Response::MODEL_EVIDENCE;
    }
}
