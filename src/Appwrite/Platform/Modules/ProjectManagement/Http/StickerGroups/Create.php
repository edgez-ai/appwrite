<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\StickerGroups;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Activity;
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
use Utopia\Validator\Nullable;
use Utopia\Validator\Text;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createStickerGroup';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/sticker-groups')
            ->desc('Create Sticker Group')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_STICKER_GROUPS)
            ->label(
                'sdk',
                Sdk::method('createStickerGroup', Response::MODEL_STICKER_GROUP, Response::STATUS_CODE_CREATED),
            )
            ->label('event', 'stickerGroups.[groupId].create')
            ->param('groupId', 'unique()', new CustomId(), 'Group ID or `unique()`.')
            ->param('title', '', new Text(256), 'Group title.')
            ->param('description', '', new Text(65535), 'Group description.', true)
            ->param('taskId', null, new Nullable(new UID()), 'Related task ID.', true)
            ->param(
                'permissions',
                null,
                new Permissions(APP_LIMIT_ARRAY_PARAMS_SIZE, [
                    Database::PERMISSION_READ,
                    Database::PERMISSION_UPDATE,
                    Database::PERMISSION_DELETE,
                    Database::PERMISSION_WRITE,
                ]),
                'Group permissions.',
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
        string $groupId,
        string $title,
        string $description,
        ?string $taskId,
        ?array $permissions,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        if ($taskId !== null && $dbForProject->getDocument('projectTasks', $taskId)->isEmpty()) {
            throw new Exception(Exception::PROJECT_TASK_NOT_FOUND);
        }
        $groupId = $groupId === 'unique()' ? ID::unique() : $groupId;
        $createHash = Idempotency::hash([
            'taskId' => $taskId,
            'title' => $title,
            'description' => $description,
            'permissions' => $permissions,
        ]);
        $group = new Document([
            '$id' => $groupId,
            '$collection' => 'stickerGroups',
            'taskId' => $taskId,
            'title' => $title,
            'description' => $description,
            'currentSummaryId' => null,
            'summaryStatus' => 'pending',
            'lastStickerAt' => null,
            'createdBy' => $user->getId(),
            'createHash' => $createHash,
            'search' => \implode(' ', [$groupId, $title, $description]),
        ]);
        (new ResourcePermissions())->setOnCreate($group, $permissions, $user, $authorization);
        $created = true;
        try {
            $group = $authorization->skip(fn () => $dbForProject->createDocument('stickerGroups', $group));
        } catch (Duplicate) {
            $created = false;
            $group = Idempotency::resolveCreateRetry($dbForProject, $authorization, 'stickerGroups', $groupId, [
                'createHash' => $createHash,
            ]);
        }
        if ($created) {
            Activity::record(
                $dbForProject,
                $authorization,
                $user,
                'stickerGroup',
                $groupId,
                'stickerGroup.create',
                [],
                $group->getPermissions(),
            );
            $queueForEvents->setParam('groupId', $groupId);
        }
        $response
            ->setStatusCode($created ? Response::STATUS_CODE_CREATED : Response::STATUS_CODE_OK)
            ->dynamic($group, Response::MODEL_STICKER_GROUP);
    }
}
