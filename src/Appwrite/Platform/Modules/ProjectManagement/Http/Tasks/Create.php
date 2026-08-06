<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Tasks;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Activity;
use Appwrite\ProjectManagement\Idempotency;
use Appwrite\ProjectManagement\Permissions as ResourcePermissions;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\ProjectManagement\TaskState;
use Appwrite\Utopia\Database\Documents\User;
use Appwrite\Utopia\Database\Validator\CustomId;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate;
use Utopia\Database\Helpers\ID;
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

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createProjectTask';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/tasks')
            ->desc('Create project task')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('createTask', Response::MODEL_PROJECT_TASK, Response::STATUS_CODE_CREATED))
            ->label('event', 'tasks.[taskId].create')
            ->param('taskId', 'unique()', new CustomId(), 'Task ID or `unique()`.', true)
            ->param('title', '', new Text(256), 'Task title.')
            ->param('description', '', new Text(65535), 'Task description.', true)
            ->param('parentTaskId', null, new Nullable(new UID()), 'Parent task ID.', true)
            ->param('status', 'draft', new WhiteList(TaskState::STATUSES), 'Workflow status.', true)
            ->param('priority', 'medium', new WhiteList(['low', 'medium', 'high', 'urgent']), 'Task priority.', true)
            ->param(
                'assigneeType',
                'human',
                new WhiteList(['human', 'project_agent', 'development_agent']),
                'Assignee type.',
                true,
            )
            ->param('assigneeId', null, new Nullable(new Text(255)), 'Assignee ID.', true)
            ->param('acceptanceCriteria', [], new JSON(), 'Acceptance criteria.', true)
            ->param('sortOrder', 0, new Integer(), 'Sibling sort order.', true)
            ->param(
                'permissions',
                null,
                new Permissions(APP_LIMIT_ARRAY_PARAMS_SIZE, [
                    Database::PERMISSION_READ,
                    Database::PERMISSION_UPDATE,
                    Database::PERMISSION_DELETE,
                    Database::PERMISSION_WRITE,
                ]),
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
        string $title,
        string $description,
        ?string $parentTaskId,
        string $status,
        string $priority,
        string $assigneeType,
        ?string $assigneeId,
        array $acceptanceCriteria,
        int $sortOrder,
        ?array $permissions,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        if ($parentTaskId !== null && $dbForProject->getDocument('projectTasks', $parentTaskId)->isEmpty()) {
            throw new Exception(Exception::PROJECT_TASK_NOT_FOUND, 'Parent task could not be found.');
        }

        $taskId = $taskId === 'unique()' ? ID::unique() : $taskId;
        $createHash = Idempotency::hash([
            'parentTaskId' => $parentTaskId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'assigneeType' => $assigneeType,
            'assigneeId' => $assigneeId,
            'acceptanceCriteria' => $acceptanceCriteria,
            'sortOrder' => $sortOrder,
            'permissions' => $permissions,
        ]);
        $task = new Document([
            '$id' => $taskId,
            '$collection' => 'projectTasks',
            'parentTaskId' => $parentTaskId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'assigneeType' => $assigneeType,
            'assigneeId' => $assigneeId,
            'claimedByType' => null,
            'claimedById' => null,
            'claimedAt' => null,
            'agentRunId' => null,
            'acceptanceCriteria' => $acceptanceCriteria,
            'sortOrder' => $sortOrder,
            'createdBy' => $user->getId(),
            'createHash' => $createHash,
            'search' => \implode(' ', [$taskId, $title, $description]),
        ]);
        (new ResourcePermissions())->setOnCreate($task, $permissions, $user, $authorization);

        $created = true;
        try {
            $task = $authorization->skip(fn () => $dbForProject->createDocument('projectTasks', $task));
        } catch (Duplicate) {
            $created = false;
            $task = Idempotency::resolveCreateRetry($dbForProject, $authorization, 'projectTasks', $taskId, [
                'createHash' => $createHash,
            ]);
        }

        if ($created) {
            Activity::record(
                $dbForProject,
                $authorization,
                $user,
                'task',
                $taskId,
                'task.create',
                [],
                $task->getPermissions(),
            );
            $queueForEvents->setParam('taskId', $taskId);
        }
        $response
            ->setStatusCode($created ? Response::STATUS_CODE_CREATED : Response::STATUS_CODE_OK)
            ->dynamic($task, Response::MODEL_PROJECT_TASK);
    }
}
