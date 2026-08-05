<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Context;

use Appwrite\ProjectManagement\Context;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Get extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'getProjectTaskContext';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/tasks/:taskId/context')
            ->desc('Get deterministic task execution context')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('getTaskContext', Response::MODEL_TASK_EXECUTION_CONTEXT))
            ->param('taskId', '', new UID(), 'Task ID.')
            ->inject('response')
            ->inject('dbForProject')
            ->callback($this->action(...));
    }

    public function action(string $taskId, Response $response, Database $dbForProject): void
    {
        $response->dynamic(Context::forTask($dbForProject, $taskId), Response::MODEL_TASK_EXECUTION_CONTEXT);
    }
}
