<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Evidence;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
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
use Utopia\Validator\JSON;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createEvidence';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/evidence')
            ->desc('Attach immutable execution evidence')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_EVIDENCE)
            ->label('sdk', Sdk::method('createEvidence', Response::MODEL_EVIDENCE, Response::STATUS_CODE_CREATED))
            ->label('event', 'evidence.[evidenceId].create')
            ->param('evidenceId', 'unique()', new CustomId(), 'Evidence ID or `unique()`.', true)
            ->param('taskId', '', new UID(), 'Task ID.')
            ->param('agentRunId', '', new UID(), 'Agent Run ID.')
            ->param(
                'type',
                '',
                new WhiteList(['pull_request', 'commit', 'build', 'test', 'deployment', 'artifact', 'log', 'link']),
                'Evidence type.',
            )
            ->param('title', '', new Text(256), 'Evidence title.', true)
            ->param('url', '', new Text(2048), 'Evidence URL.', true)
            ->param('storageFileId', '', new Text(255), 'Storage file ID.', true)
            ->param('metadata', [], new JSON(), 'Evidence metadata.', true)
            ->param(
                'permissions',
                null,
                new Permissions(APP_LIMIT_ARRAY_PARAMS_SIZE, [
                    Database::PERMISSION_READ,
                    Database::PERMISSION_UPDATE,
                    Database::PERMISSION_DELETE,
                    Database::PERMISSION_WRITE,
                ]),
                'Evidence permissions.',
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
        string $evidenceId,
        string $taskId,
        string $agentRunId,
        string $type,
        string $title,
        string $url,
        string $storageFileId,
        array $metadata,
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
        $run = $dbForProject->getDocument('agentRuns', $agentRunId);
        if ($run->isEmpty() || $run->getAttribute('taskId') !== $taskId) {
            throw new Exception(Exception::AGENT_RUN_NOT_FOUND);
        }
        $evidenceId = $evidenceId === 'unique()' ? ID::unique() : $evidenceId;
        $createHash = Idempotency::hash([
            'taskId' => $taskId,
            'agentRunId' => $agentRunId,
            'type' => $type,
            'title' => $title,
            'url' => $url,
            'storageFileId' => $storageFileId,
            'metadata' => $metadata,
            'permissions' => $permissions,
        ]);
        $evidence = new Document([
            '$id' => $evidenceId,
            '$collection' => 'evidence',
            'taskId' => $taskId,
            'agentRunId' => $agentRunId,
            'type' => $type,
            'title' => $title,
            'url' => $url,
            'storageFileId' => $storageFileId,
            'metadata' => $metadata,
            'createHash' => $createHash,
        ]);
        (new ResourcePermissions())->setOnCreate($evidence, $permissions, $user, $authorization);
        $created = true;
        try {
            $evidence = $authorization->skip(fn () => $dbForProject->createDocument('evidence', $evidence));
        } catch (Duplicate) {
            $created = false;
            $evidence = Idempotency::resolveCreateRetry($dbForProject, $authorization, 'evidence', $evidenceId, [
                'createHash' => $createHash,
            ]);
        }
        if ($created) {
            $queueForEvents->setParam('evidenceId', $evidenceId);
        }
        $response
            ->setStatusCode($created ? Response::STATUS_CODE_CREATED : Response::STATUS_CODE_OK)
            ->dynamic($evidence, Response::MODEL_EVIDENCE);
    }
}
