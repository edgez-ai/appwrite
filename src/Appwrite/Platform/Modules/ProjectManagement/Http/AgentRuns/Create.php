<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\AgentRuns;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Context;
use Appwrite\ProjectManagement\Idempotency;
use Appwrite\ProjectManagement\Permissions as ResourcePermissions;
use Appwrite\ProjectManagement\Sdk;
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
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createAgentRun';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/agent-runs')
            ->desc('Create agent run')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_AGENT_RUNS)
            ->label('sdk', Sdk::method('createAgentRun', Response::MODEL_AGENT_RUN, Response::STATUS_CODE_CREATED))
            ->label('event', 'agentRuns.[runId].create')
            ->param('runId', 'unique()', new CustomId(), 'Run ID or `unique()`.')
            ->param('taskId', '', new UID(), 'Task ID.')
            ->param('agentType', '', new WhiteList(['project_agent', 'development_agent']), 'Agent type.')
            ->param('workspaceId', '', new Text(255), 'Workspace ID.', true)
            ->param('codexThreadId', '', new Text(255), 'Codex task ID.', true)
            ->param('attempt', 1, new Integer(true), 'Attempt number.', true)
            ->param(
                'inputContextSnapshot',
                null,
                new JSON(),
                'Context snapshot. When omitted, Appwrite builds it deterministically.',
                true,
            )
            ->param(
                'permissions',
                null,
                new Permissions(APP_LIMIT_ARRAY_PARAMS_SIZE, [
                    Database::PERMISSION_READ,
                    Database::PERMISSION_UPDATE,
                    Database::PERMISSION_DELETE,
                    Database::PERMISSION_WRITE,
                ]),
                'Run permissions.',
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
        string $runId,
        string $taskId,
        string $agentType,
        string $workspaceId,
        string $codexThreadId,
        int $attempt,
        ?array $inputContextSnapshot,
        ?array $permissions,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        if ($dbForProject->getDocument('projectTasks', $taskId)->isEmpty()) {
            throw new Exception(Exception::PROJECT_TASK_NOT_FOUND);
        }
        $runId = $runId === 'unique()' ? ID::unique() : $runId;
        $contextHashInput = $inputContextSnapshot ?? '__appwrite_generated__';
        $inputContextSnapshot ??= Context::forTask($dbForProject, $taskId)->getArrayCopy();
        $createHash = Idempotency::hash([
            'taskId' => $taskId,
            'agentType' => $agentType,
            'workspaceId' => $workspaceId,
            'codexThreadId' => $codexThreadId,
            'attempt' => $attempt,
            'inputContextSnapshot' => $contextHashInput,
            'permissions' => $permissions,
        ]);
        $run = new Document([
            '$id' => $runId,
            '$collection' => 'agentRuns',
            'taskId' => $taskId,
            'agentType' => $agentType,
            'status' => 'queued',
            'workspaceId' => $workspaceId,
            'codexThreadId' => $codexThreadId,
            'attempt' => $attempt,
            'inputContextSnapshot' => $inputContextSnapshot,
            'resultSummary' => '',
            'startedAt' => null,
            'completedAt' => null,
            'createHash' => $createHash,
            'search' => \implode(' ', [$runId, $taskId, $agentType]),
        ]);
        (new ResourcePermissions())->setOnCreate($run, $permissions, $user, $authorization);
        $created = true;
        try {
            $run = $authorization->skip(fn () => $dbForProject->createDocument('agentRuns', $run));
        } catch (Duplicate) {
            $created = false;
            $run = Idempotency::resolveCreateRetry($dbForProject, $authorization, 'agentRuns', $runId, [
                'createHash' => $createHash,
            ]);
        }
        if ($created) {
            $queueForEvents->setParam('runId', $runId);
        }
        $response
            ->setStatusCode($created ? Response::STATUS_CODE_CREATED : Response::STATUS_CODE_OK)
            ->dynamic($run, Response::MODEL_AGENT_RUN);
    }
}
