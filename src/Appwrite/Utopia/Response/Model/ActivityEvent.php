<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;

class ActivityEvent extends ProjectResource
{
    public function __construct()
    {
        $this->addResourceRules('Activity Event')
            ->addRule('entityType', [
                'type' => self::TYPE_STRING,
                'description' => 'Entity type.',
                'default' => '',
                'example' => 'sticker',
            ])
            ->addRule('entityId', [
                'type' => self::TYPE_STRING,
                'description' => 'Entity ID.',
                'default' => '',
                'example' => 'sticker_123',
            ])
            ->addRule('eventType', [
                'type' => self::TYPE_STRING,
                'description' => 'Activity type.',
                'default' => '',
                'example' => 'sticker.status.update',
            ])
            ->addRule('actorType', [
                'type' => self::TYPE_STRING,
                'description' => 'Actor type.',
                'default' => 'user',
                'example' => 'agent',
            ])
            ->addRule('actorId', [
                'type' => self::TYPE_STRING,
                'description' => 'Actor ID.',
                'required' => false,
                'default' => null,
                'example' => 'user_123',
            ])
            ->addRule('payload', [
                'type' => self::TYPE_JSON,
                'description' => 'Activity payload.',
                'default' => [],
                'example' => ['status' => 'invalid'],
            ]);
    }

    public function getName(): string
    {
        return 'Activity Event';
    }

    public function getType(): string
    {
        return Response::MODEL_ACTIVITY_EVENT;
    }
}
