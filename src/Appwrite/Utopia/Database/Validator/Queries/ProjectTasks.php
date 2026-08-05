<?php

namespace Appwrite\Utopia\Database\Validator\Queries;

class ProjectTasks extends Base
{
    public const ALLOWED_ATTRIBUTES = [
        'parentTaskId',
        'title',
        'status',
        'priority',
        'assigneeType',
        'assigneeId',
        'claimedByType',
        'claimedById',
        'agentRunId',
        'sortOrder',
    ];

    public function __construct()
    {
        parent::__construct('projectTasks', self::ALLOWED_ATTRIBUTES);
    }
}
