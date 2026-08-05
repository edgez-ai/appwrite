<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;

class StickerGroup extends ProjectResource
{
    public function __construct()
    {
        $this->addResourceRules('Sticker Group')
            ->addRule('taskId', [
                'type' => self::TYPE_STRING,
                'description' => 'Related task ID.',
                'required' => false,
                'default' => null,
                'example' => 'task_123',
            ])
            ->addRule('title', [
                'type' => self::TYPE_STRING,
                'description' => 'Group title.',
                'default' => '',
                'example' => 'BLE Initial Connection',
            ])
            ->addRule('description', [
                'type' => self::TYPE_STRING,
                'description' => 'Group description.',
                'default' => '',
                'example' => 'Connection facts and decisions.',
            ])
            ->addRule('currentSummaryId', [
                'type' => self::TYPE_STRING,
                'description' => 'Current versioned summary ID.',
                'required' => false,
                'default' => null,
                'example' => 'summary_123',
            ])
            ->addRule('summaryStatus', [
                'type' => self::TYPE_STRING,
                'description' => 'Summary generation status.',
                'default' => 'pending',
                'example' => 'stale',
            ])
            ->addRule('lastStickerAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Time of the latest contextual Sticker change.',
                'required' => false,
                'default' => null,
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('createdBy', [
                'type' => self::TYPE_STRING,
                'description' => 'User that created the group.',
                'required' => false,
                'default' => null,
                'example' => 'user_123',
            ]);
    }

    public function getName(): string
    {
        return 'Sticker Group';
    }

    public function getType(): string
    {
        return Response::MODEL_STICKER_GROUP;
    }
}
