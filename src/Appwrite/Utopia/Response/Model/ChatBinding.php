<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;

class ChatBinding extends ProjectResource
{
    public function __construct()
    {
        $this->addResourceRules('Chat Binding')
            ->addRule('provider', [
                'type' => self::TYPE_STRING,
                'description' => 'Chat provider.',
                'default' => '',
                'example' => 'slack',
            ])
            ->addRule('externalTenantId', [
                'type' => self::TYPE_STRING,
                'description' => 'Provider tenant ID.',
                'default' => '',
                'example' => 'T123',
            ])
            ->addRule('conversationId', [
                'type' => self::TYPE_STRING,
                'description' => 'Conversation ID.',
                'default' => '',
                'example' => 'C123',
            ])
            ->addRule('threadId', [
                'type' => self::TYPE_STRING,
                'description' => 'Optional thread ID.',
                'default' => '',
                'example' => '171234.5678',
            ])
            ->addRule('triggerMode', [
                'type' => self::TYPE_STRING,
                'description' => 'Agent trigger mode.',
                'default' => 'mention_only',
                'example' => 'mention_only',
            ]);
    }

    public function getName(): string
    {
        return 'Chat Binding';
    }

    public function getType(): string
    {
        return Response::MODEL_CHAT_BINDING;
    }
}
