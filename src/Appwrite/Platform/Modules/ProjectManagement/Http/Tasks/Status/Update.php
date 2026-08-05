<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Status;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Activity;
use Appwrite\ProjectManagement\Permissions as ResourcePermissions;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\ProjectManagement\TaskState;
use Appwrite\Utopia\Database\Documents\User;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Update extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'updateProjectTaskStatus';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_PATCH)
            ->setHttpPath('/v1/tasks/:taskId/status')
            ->desc('Transition task status with optimistic concurrency')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('updateTaskStatus', Response::MODEL_PROJECT_TASK))
            ->label('event', 'tasks.[taskId].update.status')
            ->param('taskId', '', new UID(), 'Task ID.')
            ->param('expectedStatus', '', new WhiteList(TaskState::STATUSES), 'Status observed by the agent.')
            ->param('status', '', new WhiteList(TaskState::STATUSES), 'Requested next status.')
            ->param('reason', '', new Text(65535), 'Machine- or human-readable transition reason.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $taskId,
        string $expectedStatus,
        string $status,
        string $reason,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $changed = false;
        $transitionTask = function () use (
            $dbForProject,
            $taskId,
            $expectedStatus,
            $status,
            $user,
            $authorization,
            &$changed,
        ): Document {
            $task = $dbForProject->getDocument('projectTasks', $taskId, forUpdate: true);
            if ($task->isEmpty()) {
                throw new Exception(Exception::PROJECT_TASK_NOT_FOUND);
            }
            (new ResourcePermissions())->assert($task, Database::PERMISSION_UPDATE, $user, $authorization);

            $currentStatus = (string) $task->getAttribute('status', 'draft');
            if ($currentStatus !== $expectedStatus) {
                throw new Exception(
                    Exception::PROJECT_TASK_STATUS_CONFLICT,
                    "Expected status '{$expectedStatus}', but current status is '{$currentStatus}'. Reload the task and retry.",
                );
            }
            if (!TaskState::canTransition($currentStatus, $status)) {
                throw new Exception(
                    Exception::PROJECT_TASK_STATUS_CONFLICT,
                    "Task cannot transition from '{$currentStatus}' to '{$status}'.",
                );
            }

            if ($currentStatus === $status) {
                return $task;
            }

            $changed = true;
            return $dbForProject->updateDocument('projectTasks', $taskId, new Document(['status' => $status]));
        };
        $task = $authorization->skip(fn () => $dbForProject->withTransaction($transitionTask));

        if ($changed) {
            Activity::record(
                $dbForProject,
                $authorization,
                $user,
                'task',
                $taskId,
                'task.status.update',
                [
                    'expectedStatus' => $expectedStatus,
                    'status' => $status,
                    'reason' => $reason,
                ],
                $task->getPermissions(),
            );
            $queueForEvents->setParam('taskId', $taskId);
        }
        $response->dynamic($task, Response::MODEL_PROJECT_TASK);
    }
}
