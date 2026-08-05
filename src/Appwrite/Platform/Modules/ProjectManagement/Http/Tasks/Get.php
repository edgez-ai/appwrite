<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Tasks;

use Appwrite\Extend\Exception;
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
        return 'getProjectTask';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/tasks/:taskId')
            ->desc('Get project task')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('getTask', Response::MODEL_PROJECT_TASK))
            ->param('taskId', '', new UID(), 'Task ID.')
            ->inject('response')
            ->inject('dbForProject')
            ->callback($this->action(...));
    }

    public function action(string $taskId, Response $response, Database $dbForProject): void
    {
        $task = $dbForProject->getDocument('projectTasks', $taskId);
        if ($task->isEmpty()) {
            throw new Exception(Exception::PROJECT_TASK_NOT_FOUND);
        }
        $response->dynamic($task, Response::MODEL_PROJECT_TASK);
    }
}
