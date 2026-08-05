<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Tasks\Claim;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Activity;
use Appwrite\ProjectManagement\Permissions as ResourcePermissions;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Documents\User;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\DateTime;
use Utopia\Database\Document;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Nullable;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Update extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'claimProjectTask';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_PATCH)
            ->setHttpPath('/v1/tasks/:taskId/claim')
            ->desc('Atomically claim a ready task for an agent')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('claimTask', Response::MODEL_PROJECT_TASK))
            ->label('event', 'tasks.[taskId].claim')
            ->param('taskId', '', new UID(), 'Task ID.')
            ->param('agentId', '', new Text(255), 'Stable agent or worker ID.')
            ->param(
                'agentType',
                'development_agent',
                new WhiteList(['project_agent', 'development_agent']),
                'Agent type.',
                true,
            )
            ->param('agentRunId', null, new Nullable(new UID()), 'Agent Run associated with this claim.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $taskId,
        string $agentId,
        string $agentType,
        ?string $agentRunId,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        if ($agentRunId !== null) {
            $run = $dbForProject->getDocument('agentRuns', $agentRunId);
            if ($run->isEmpty() || $run->getAttribute('taskId') !== $taskId) {
                throw new Exception(Exception::AGENT_RUN_NOT_FOUND);
            }
        }

        $claimed = false;
        $claimTask = function () use (
            $dbForProject,
            $taskId,
            $agentId,
            $agentType,
            $agentRunId,
            $user,
            $authorization,
            &$claimed,
        ): Document {
            $task = $dbForProject->getDocument('projectTasks', $taskId, forUpdate: true);
            if ($task->isEmpty()) {
                throw new Exception(Exception::PROJECT_TASK_NOT_FOUND);
            }
            (new ResourcePermissions())->assert($task, Database::PERMISSION_UPDATE, $user, $authorization);

            if (
                $task->getAttribute('status') === 'in_progress' &&
                $task->getAttribute('claimedById') === $agentId &&
                $task->getAttribute('claimedByType') === $agentType &&
                $task->getAttribute('agentRunId') === $agentRunId
            ) {
                return $task;
            }
            if ($task->getAttribute('status') !== 'ready') {
                throw new Exception(
                    Exception::PROJECT_TASK_STATUS_CONFLICT,
                    "Only a ready task can be claimed; current status is '{$task->getAttribute('status')}'.",
                );
            }

            $claimed = true;
            return $dbForProject->updateDocument(
                'projectTasks',
                $taskId,
                new Document([
                    'status' => 'in_progress',
                    'assigneeType' => $agentType,
                    'assigneeId' => $agentId,
                    'claimedByType' => $agentType,
                    'claimedById' => $agentId,
                    'claimedAt' => DateTime::now(),
                    'agentRunId' => $agentRunId,
                ]),
            );
        };
        $task = $authorization->skip(fn () => $dbForProject->withTransaction($claimTask));

        if ($claimed) {
            Activity::record(
                $dbForProject,
                $authorization,
                $user,
                'task',
                $taskId,
                'task.claim',
                [
                    'agentId' => $agentId,
                    'agentType' => $agentType,
                    'agentRunId' => $agentRunId,
                ],
                $task->getPermissions(),
            );
            $queueForEvents->setParam('taskId', $taskId);
        }
        $response->dynamic($task, Response::MODEL_PROJECT_TASK);
    }
}
