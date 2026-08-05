<?php

namespace Appwrite\Utopia\Database\Validator\Queries;

class Evidence extends Base
{
    public const ALLOWED_ATTRIBUTES = ['taskId', 'agentRunId', 'type', 'storageFileId'];

    public function __construct()
    {
        parent::__construct('evidence', self::ALLOWED_ATTRIBUTES);
    }
}
