<?php

namespace Appwrite\ProjectManagement;

class ChatBindingKey
{
    public static function generate(
        string $provider,
        string $externalTenantId,
        string $conversationId,
        string $threadId,
    ): string {
        return \hash('sha256', \implode("\0", [$provider, $externalTenantId, $conversationId, $threadId]));
    }
}
