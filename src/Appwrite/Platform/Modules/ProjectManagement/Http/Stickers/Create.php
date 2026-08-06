<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Stickers;

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
use Utopia\Database\DateTime;
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
use Utopia\Validator\WhiteList;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createSticker';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/stickers')
            ->desc('Create immutable Sticker')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_STICKERS)
            ->label('sdk', Sdk::method('createSticker', Response::MODEL_STICKER, Response::STATUS_CODE_CREATED))
            ->label('event', 'stickers.[stickerId].create')
            ->param('stickerId', 'unique()', new CustomId(), 'Sticker ID or `unique()`.', true)
            ->param(
                'type',
                'note',
                new WhiteList(['note', 'fact', 'decision', 'requirement', 'risk', 'question', 'constraint', 'result']),
                'Sticker type.',
            )
            ->param('content', '', new Text(65535), 'Immutable Sticker content.')
            ->param('title', null, new Nullable(new Text(256)), 'Sticker title.', true)
            ->param('taskId', null, new Nullable(new UID()), 'Related task ID.', true)
            ->param('groupId', null, new Nullable(new UID()), 'Related Sticker Group ID.', true)
            ->param(
                'sourceType',
                'manual',
                new WhiteList(['manual', 'slack', 'teams', 'wecom', 'agent', 'api']),
                'Sticker source.',
                true,
            )
            ->param('sourceId', null, new Nullable(new Text(255)), 'External source ID.', true)
            ->param('supersedesStickerId', null, new Nullable(new UID()), 'Sticker replaced by this Sticker.', true)
            ->param(
                'permissions',
                null,
                new Permissions(APP_LIMIT_ARRAY_PARAMS_SIZE, [
                    Database::PERMISSION_READ,
                    Database::PERMISSION_UPDATE,
                    Database::PERMISSION_DELETE,
                    Database::PERMISSION_WRITE,
                ]),
                'Sticker permissions.',
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
        string $stickerId,
        string $type,
        string $content,
        ?string $title,
        ?string $taskId,
        ?string $groupId,
        string $sourceType,
        ?string $sourceId,
        ?string $supersedesStickerId,
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
        $group = $groupId === null ? new Document() : $dbForProject->getDocument('stickerGroups', $groupId);
        if ($groupId !== null && $group->isEmpty()) {
            throw new Exception(Exception::STICKER_GROUP_NOT_FOUND);
        }
        $superseded =
            $supersedesStickerId === null
                ? new Document()
                : $authorization->skip(fn () => $dbForProject->getDocument('stickers', $supersedesStickerId));
        if ($supersedesStickerId !== null && $superseded->isEmpty()) {
            throw new Exception(Exception::STICKER_NOT_FOUND, 'The superseded Sticker could not be found.');
        }
        if (!$superseded->isEmpty()) {
            (new ResourcePermissions())->assert($superseded, Database::PERMISSION_UPDATE, $user, $authorization);
        }

        $stickerId = $stickerId === 'unique()' ? ID::unique() : $stickerId;
        $createHash = Idempotency::hash([
            'taskId' => $taskId,
            'groupId' => $groupId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
            'supersedesStickerId' => $supersedesStickerId,
            'permissions' => $permissions,
        ]);
        $roles = $authorization->getRoles();
        $sticker = new Document([
            '$id' => $stickerId,
            '$collection' => 'stickers',
            'taskId' => $taskId,
            'groupId' => $groupId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'status' => 'valid',
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
            'createdByType' => $user->isKey($roles) ? 'agent' : 'user',
            'createdById' => $user->getId(),
            'statusChangedBy' => null,
            'statusChangedAt' => null,
            'statusReason' => '',
            'supersedesStickerId' => $supersedesStickerId,
            'supersededByStickerId' => null,
            'createHash' => $createHash,
            'search' => \implode(' ', [$stickerId, $title ?? '', $content]),
        ]);
        (new ResourcePermissions())->setOnCreate($sticker, $permissions, $user, $authorization);
        $created = true;
        try {
            $sticker = $authorization->skip(fn () => $dbForProject->createDocument('stickers', $sticker));
        } catch (Duplicate) {
            $created = false;
            $sticker = Idempotency::resolveCreateRetry($dbForProject, $authorization, 'stickers', $stickerId, [
                'createHash' => $createHash,
            ]);
        }

        if ($created && !$superseded->isEmpty()) {
            $authorization->skip(
                fn () => $dbForProject->updateDocument(
                    'stickers',
                    $supersedesStickerId,
                    new Document([
                        'status' => 'superseded',
                        'statusChangedBy' => $user->getId(),
                        'statusChangedAt' => DateTime::now(),
                        'statusReason' => 'Superseded by a newer Sticker.',
                        'supersededByStickerId' => $stickerId,
                    ]),
                ),
            );
        }
        if ($created && !$group->isEmpty()) {
            $authorization->skip(
                fn () => $dbForProject->updateDocument(
                    'stickerGroups',
                    $groupId,
                    new Document([
                        'summaryStatus' => 'stale',
                        'lastStickerAt' => DateTime::now(),
                    ]),
                ),
            );
        }

        if ($created) {
            Activity::record(
                $dbForProject,
                $authorization,
                $user,
                'sticker',
                $stickerId,
                'sticker.create',
                ['groupId' => $groupId, 'taskId' => $taskId],
                $sticker->getPermissions(),
            );
            $queueForEvents->setParam('stickerId', $stickerId);
        }
        $response
            ->setStatusCode($created ? Response::STATUS_CODE_CREATED : Response::STATUS_CODE_OK)
            ->dynamic($sticker, Response::MODEL_STICKER);
    }
}
