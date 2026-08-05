<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;

class Sticker extends ProjectResource
{
    public function __construct()
    {
        $this->addResourceRules('Sticker')
            ->addRule('taskId', [
                'type' => self::TYPE_STRING,
                'description' => 'Related task ID.',
                'required' => false,
                'default' => null,
                'example' => 'task_123',
            ])
            ->addRule('groupId', [
                'type' => self::TYPE_STRING,
                'description' => 'Related Sticker Group ID.',
                'required' => false,
                'default' => null,
                'example' => 'group_123',
            ])
            ->addRule('type', [
                'type' => self::TYPE_STRING,
                'description' => 'Sticker type.',
                'default' => 'note',
                'example' => 'decision',
            ])
            ->addRule('title', [
                'type' => self::TYPE_STRING,
                'description' => 'Optional Sticker title.',
                'required' => false,
                'default' => null,
                'example' => 'BLE startup decision',
            ])
            ->addRule('content', [
                'type' => self::TYPE_STRING,
                'description' => 'Immutable Sticker content.',
                'default' => '',
                'example' => 'Optimize Android initialization order.',
            ])
            ->addRule('status', [
                'type' => self::TYPE_STRING,
                'description' => 'Sticker validity.',
                'default' => 'valid',
                'example' => 'valid',
            ])
            ->addRule('sourceType', [
                'type' => self::TYPE_STRING,
                'description' => 'Sticker source.',
                'default' => 'manual',
                'example' => 'slack',
            ])
            ->addRule('sourceId', [
                'type' => self::TYPE_STRING,
                'description' => 'External source ID.',
                'required' => false,
                'default' => null,
                'example' => 'message_123',
            ])
            ->addRule('createdByType', [
                'type' => self::TYPE_STRING,
                'description' => 'Creator type.',
                'default' => 'user',
                'example' => 'agent',
            ])
            ->addRule('createdById', [
                'type' => self::TYPE_STRING,
                'description' => 'Creator ID.',
                'required' => false,
                'default' => null,
                'example' => 'user_123',
            ])
            ->addRule('statusChangedBy', [
                'type' => self::TYPE_STRING,
                'description' => 'Actor that last changed validity.',
                'required' => false,
                'default' => null,
                'example' => 'user_123',
            ])
            ->addRule('statusChangedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Last validity change time.',
                'required' => false,
                'default' => null,
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('statusReason', [
                'type' => self::TYPE_STRING,
                'description' => 'Validity change reason.',
                'default' => '',
                'example' => 'Replaced by repeated test result.',
            ])
            ->addRule('supersedesStickerId', [
                'type' => self::TYPE_STRING,
                'description' => 'Sticker replaced by this Sticker.',
                'required' => false,
                'default' => null,
                'example' => 'sticker_old',
            ])
            ->addRule('supersededByStickerId', [
                'type' => self::TYPE_STRING,
                'description' => 'Sticker that replaced this Sticker.',
                'required' => false,
                'default' => null,
                'example' => 'sticker_new',
            ]);
    }

    public function getName(): string
    {
        return 'Sticker';
    }

    public function getType(): string
    {
        return Response::MODEL_STICKER;
    }
}
