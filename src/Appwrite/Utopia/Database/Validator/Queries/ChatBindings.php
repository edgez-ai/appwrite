<?php

namespace Appwrite\Utopia\Database\Validator\Queries;

class ChatBindings extends Base
{
    public const ALLOWED_ATTRIBUTES = ['provider', 'externalTenantId', 'conversationId', 'threadId', 'triggerMode'];

    public function __construct()
    {
        parent::__construct('chatBindings', self::ALLOWED_ATTRIBUTES);
    }
}
