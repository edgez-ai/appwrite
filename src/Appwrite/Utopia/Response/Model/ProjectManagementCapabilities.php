<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;

class ProjectManagementCapabilities extends Model
{
    public function __construct()
    {
        $this->addRule('schemaVersion', [
            'type' => self::TYPE_STRING,
            'description' => 'Project management agent contract version.',
            'default' => '',
            'example' => '2026-08-06',
        ])
            ->addRule('service', [
                'type' => self::TYPE_STRING,
                'description' => 'Service identifier.',
                'default' => 'projectManagement',
                'example' => 'projectManagement',
            ])
            ->addRule('principles', [
                'type' => self::TYPE_STRING,
                'description' => 'Rules agents should follow when using this API.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('taskStatuses', [
                'type' => self::TYPE_STRING,
                'description' => 'Supported task statuses.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('taskTransitions', [
                'type' => self::TYPE_JSON,
                'description' => 'Legal next statuses keyed by current task status.',
                'default' => [],
            ])
            ->addRule('stickerStatuses', [
                'type' => self::TYPE_STRING,
                'description' => 'Supported Sticker statuses.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('stickerTypes', [
                'type' => self::TYPE_STRING,
                'description' => 'Supported Sticker knowledge types.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('contextEndpoints', [
                'type' => self::TYPE_JSON,
                'description' => 'Endpoints that provide bounded execution context.',
                'default' => [],
            ])
            ->addRule('retryableErrors', [
                'type' => self::TYPE_JSON,
                'description' => 'Recoverable error codes and agent recovery instructions.',
                'default' => [],
            ])
            ->addRule('agentActions', [
                'type' => self::TYPE_JSON,
                'description' => 'High-level agent actions, paths, and retry semantics.',
                'default' => [],
            ]);
    }

    public function getName(): string
    {
        return 'Project Management Capabilities';
    }

    public function getType(): string
    {
        return Response::MODEL_PROJECT_MANAGEMENT_CAPABILITIES;
    }
}
