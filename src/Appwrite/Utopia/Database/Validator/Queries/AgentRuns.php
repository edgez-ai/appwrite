<?php

namespace Appwrite\Utopia\Database\Validator\Queries;

class AgentRuns extends Base
{
    public const ALLOWED_ATTRIBUTES = ['taskId', 'agentType', 'status', 'workspaceId', 'codexThreadId', 'attempt'];

    public function __construct()
    {
        parent::__construct('agentRuns', self::ALLOWED_ATTRIBUTES);
    }
}
