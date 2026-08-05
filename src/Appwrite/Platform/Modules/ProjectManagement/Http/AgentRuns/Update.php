<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\AgentRuns;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\AgentRunState;
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
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Update extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'updateAgentRun';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_PATCH)
            ->setHttpPath('/v1/agent-runs/:runId')
            ->desc('Update agent run state')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_AGENT_RUNS)
            ->label('sdk', Sdk::method('updateAgentRun', Response::MODEL_AGENT_RUN))
            ->label('event', 'agentRuns.[runId].update')
            ->param('runId', '', new UID(), 'Run ID.')
            ->param('expectedStatus', '', new WhiteList(AgentRunState::STATUSES), 'Status observed by the agent.')
            ->param('status', '', new WhiteList(AgentRunState::STATUSES), 'Run status.')
            ->param('resultSummary', '', new Text(65535), 'Result summary.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $runId,
        string $expectedStatus,
        string $status,
        string $resultSummary,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $changed = false;
        $transitionRun = function () use (
            $dbForProject,
            $runId,
            $expectedStatus,
            $status,
            $resultSummary,
            $user,
            $authorization,
            &$changed,
        ): Document {
            $run = $dbForProject->getDocument('agentRuns', $runId, forUpdate: true);
            if ($run->isEmpty()) {
                throw new Exception(Exception::AGENT_RUN_NOT_FOUND);
            }
            (new ResourcePermissions())->assert($run, Database::PERMISSION_UPDATE, $user, $authorization);

            $currentStatus = (string) $run->getAttribute('status', 'queued');
            if ($currentStatus !== $expectedStatus) {
                throw new Exception(
                    Exception::AGENT_RUN_STATUS_CONFLICT,
                    "Expected status '{$expectedStatus}', but current status is '{$currentStatus}'.",
                );
            }
            if (!AgentRunState::canTransition($currentStatus, $status)) {
                throw new Exception(
                    Exception::AGENT_RUN_STATUS_CONFLICT,
                    "Agent Run cannot transition from '{$currentStatus}' to '{$status}'.",
                );
            }
            if ($currentStatus === $status && $run->getAttribute('resultSummary', '') === $resultSummary) {
                return $run;
            }

            $changes = new Document(['status' => $status, 'resultSummary' => $resultSummary]);
            if ($status === 'running' && empty($run->getAttribute('startedAt'))) {
                $changes->setAttribute('startedAt', DateTime::now());
            }
            if (\in_array($status, ['succeeded', 'failed', 'cancelled'], true)) {
                $changes->setAttribute('completedAt', DateTime::now());
            }
            $changed = true;
            return $dbForProject->updateDocument('agentRuns', $runId, $changes);
        };
        $run = $authorization->skip(fn () => $dbForProject->withTransaction($transitionRun));
        if ($changed) {
            $queueForEvents->setParam('runId', $runId);
        }
        $response->dynamic($run, Response::MODEL_AGENT_RUN);
    }
}
