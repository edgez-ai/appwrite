<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;

class StickerGroupSummary extends ProjectResource
{
    public function __construct()
    {
        $this->addResourceRules('Sticker Group Summary')
            ->addRule('groupId', [
                'type' => self::TYPE_STRING,
                'description' => 'Sticker Group ID.',
                'default' => '',
                'example' => 'group_123',
            ])
            ->addRule('version', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Summary version.',
                'default' => 1,
                'example' => 2,
            ])
            ->addRule('content', [
                'type' => self::TYPE_STRING,
                'description' => 'Rendered summary.',
                'default' => '',
                'example' => 'Confirmed facts and decisions.',
            ])
            ->addRule('structuredContent', [
                'type' => self::TYPE_JSON,
                'description' => 'Structured summary.',
                'default' => [],
                'example' => ['decisions' => ['Optimize startup order.']],
            ])
            ->addRule('basedOnStickerIds', [
                'type' => self::TYPE_STRING,
                'description' => 'Sticker IDs used for this version.',
                'default' => [],
                'example' => ['sticker_123'],
                'array' => true,
            ])
            ->addRule('model', [
                'type' => self::TYPE_STRING,
                'description' => 'Generating model.',
                'default' => '',
                'example' => 'project-summary-v1',
            ])
            ->addRule('promptVersion', [
                'type' => self::TYPE_STRING,
                'description' => 'Summary prompt version.',
                'default' => '',
                'example' => 'v1',
            ])
            ->addRule('generationStatus', [
                'type' => self::TYPE_STRING,
                'description' => 'Generation result.',
                'default' => 'ready',
                'example' => 'ready',
            ])
            ->addRule('generationError', [
                'type' => self::TYPE_STRING,
                'description' => 'Generation error.',
                'default' => '',
                'example' => '',
            ]);
    }

    public function getName(): string
    {
        return 'Sticker Group Summary';
    }

    public function getType(): string
    {
        return Response::MODEL_STICKER_GROUP_SUMMARY;
    }
}
