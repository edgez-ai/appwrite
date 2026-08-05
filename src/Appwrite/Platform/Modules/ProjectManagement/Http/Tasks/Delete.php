<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Tasks;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Permissions as ResourcePermissions;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Documents\User;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Delete extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'deleteProjectTask';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_DELETE)
            ->setHttpPath('/v1/tasks/:taskId')
            ->desc('Delete project task')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('deleteTask', Response::MODEL_NONE, Response::STATUS_CODE_NOCONTENT))
            ->label('event', 'tasks.[taskId].delete')
            ->param('taskId', '', new UID(), 'Task ID.')
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $taskId,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $task = $authorization->skip(fn () => $dbForProject->getDocument('projectTasks', $taskId));
        if ($task->isEmpty()) {
            throw new Exception(Exception::PROJECT_TASK_NOT_FOUND);
        }
        (new ResourcePermissions())->assert($task, Database::PERMISSION_DELETE, $user, $authorization);
        if (
            $authorization->skip(
                fn () => $dbForProject->count('projectTasks', [Query::equal('parentTaskId', [$taskId])], 1),
            ) > 0
        ) {
            throw new Exception(
                Exception::GENERAL_ARGUMENT_INVALID,
                'Move or delete child tasks before deleting this task.',
            );
        }
        $authorization->skip(fn () => $dbForProject->deleteDocument('projectTasks', $taskId));
        $queueForEvents->setParam('taskId', $taskId);
        $response->noContent();
    }
}
