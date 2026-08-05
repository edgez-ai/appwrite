<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Tasks;

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
use Utopia\Database\Validator\Permissions;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Integer;
use Utopia\Validator\JSON;
use Utopia\Validator\Nullable;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Update extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'updateProjectTask';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_PATCH)
            ->setHttpPath('/v1/tasks/:taskId')
            ->desc('Update project task')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('updateTask', Response::MODEL_PROJECT_TASK))
            ->label('event', 'tasks.[taskId].update')
            ->param('taskId', '', new UID(), 'Task ID.')
            ->param('title', null, new Nullable(new Text(256)), 'Task title.', true)
            ->param('description', null, new Nullable(new Text(65535)), 'Task description.', true)
            ->param('parentTaskId', null, new Nullable(new UID()), 'Parent task ID.', true)
            ->param('status', null, new Nullable(new WhiteList(TaskState::STATUSES)), 'Workflow status.', true)
            ->param(
                'priority',
                null,
                new Nullable(new WhiteList(['low', 'medium', 'high', 'urgent'])),
                'Task priority.',
                true,
            )
            ->param(
                'assigneeType',
                null,
                new Nullable(new WhiteList(['human', 'project_agent', 'development_agent'])),
                'Assignee type.',
                true,
            )
            ->param('assigneeId', null, new Nullable(new Text(255)), 'Assignee ID.', true)
            ->param('acceptanceCriteria', null, new Nullable(new JSON()), 'Acceptance criteria.', true)
            ->param('sortOrder', null, new Nullable(new Integer()), 'Sibling sort order.', true)
            ->param(
                'permissions',
                null,
                new Nullable(
                    new Permissions(APP_LIMIT_ARRAY_PARAMS_SIZE, [
                        Database::PERMISSION_READ,
                        Database::PERMISSION_UPDATE,
                        Database::PERMISSION_DELETE,
                        Database::PERMISSION_WRITE,
                    ]),
                ),
                'Task permissions.',
                true,
            )
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $taskId,
        ?string $title,
        ?string $description,
        ?string $parentTaskId,
        ?string $status,
        ?string $priority,
        ?string $assigneeType,
        ?string $assigneeId,
        ?array $acceptanceCriteria,
        ?int $sortOrder,
        ?array $permissions,
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
        (new ResourcePermissions())->assert($task, Database::PERMISSION_UPDATE, $user, $authorization);

        $currentStatus = (string) $task->getAttribute('status', 'draft');
        if ($status !== null && !TaskState::canTransition($currentStatus, $status)) {
            throw new Exception(
                Exception::PROJECT_TASK_STATUS_CONFLICT,
                "Task cannot transition from '{$currentStatus}' to '{$status}'.",
            );
        }

        if ($parentTaskId === $taskId) {
            throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'A task cannot be its own parent.');
        }
        if ($parentTaskId !== null && $dbForProject->getDocument('projectTasks', $parentTaskId)->isEmpty()) {
            throw new Exception(Exception::PROJECT_TASK_NOT_FOUND, 'Parent task could not be found.');
        }

        $changes = new Document();
        foreach (
            [
                'title' => $title,
                'description' => $description,
                'parentTaskId' => $parentTaskId,
                'status' => $status,
                'priority' => $priority,
                'assigneeType' => $assigneeType,
                'assigneeId' => $assigneeId,
                'acceptanceCriteria' => $acceptanceCriteria,
                'sortOrder' => $sortOrder,
            ]
            as $key => $value
        ) {
            if ($value !== null) {
                $changes->setAttribute($key, $value);
            }
        }
        if ($title !== null || $description !== null) {
            $changes->setAttribute(
                'search',
                \implode(' ', [
                    $taskId,
                    $title ?? $task->getAttribute('title', ''),
                    $description ?? $task->getAttribute('description', ''),
                ]),
            );
        }
        (new ResourcePermissions())->setOnUpdate($changes, $permissions, $user, $authorization);
        $task = $authorization->skip(fn () => $dbForProject->updateDocument('projectTasks', $taskId, $changes));

        Activity::record(
            $dbForProject,
            $authorization,
            $user,
            'task',
            $taskId,
            'task.update',
            $changes->getArrayCopy(),
            $task->getPermissions(),
        );
        $queueForEvents->setParam('taskId', $taskId);
        $response->dynamic($task, Response::MODEL_PROJECT_TASK);
    }
}
