<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Stickers\Status;

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
        return 'updateStickerStatus';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_PATCH)
            ->setHttpPath('/v1/stickers/:stickerId/status')
            ->desc('Update Sticker validity')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_STICKERS)
            ->label('sdk', Sdk::method('updateStickerStatus', Response::MODEL_STICKER))
            ->label('event', 'stickers.[stickerId].update.status')
            ->param('stickerId', '', new UID(), 'Sticker ID.')
            ->param('status', '', new WhiteList(['valid', 'invalid', 'superseded']), 'Sticker validity.')
            ->param('reason', '', new Text(65535), 'Reason for the change.', true)
            ->param('supersededByStickerId', null, new Nullable(new UID()), 'Replacement Sticker ID.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $stickerId,
        string $status,
        string $reason,
        ?string $supersededByStickerId,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $sticker = $authorization->skip(fn () => $dbForProject->getDocument('stickers', $stickerId));
        if ($sticker->isEmpty()) {
            throw new Exception(Exception::STICKER_NOT_FOUND);
        }
        (new ResourcePermissions())->assert($sticker, Database::PERMISSION_UPDATE, $user, $authorization);
        if ($status === 'superseded' && $supersededByStickerId === null) {
            throw new Exception(
                Exception::GENERAL_ARGUMENT_INVALID,
                'A superseded Sticker requires supersededByStickerId.',
            );
        }
        if (
            $supersededByStickerId !== null &&
            $dbForProject->getDocument('stickers', $supersededByStickerId)->isEmpty()
        ) {
            throw new Exception(Exception::STICKER_NOT_FOUND, 'The replacement Sticker could not be found.');
        }

        $changes = new Document([
            'status' => $status,
            'statusChangedBy' => $user->getId(),
            'statusChangedAt' => DateTime::now(),
            'statusReason' => $reason,
            'supersededByStickerId' => $status === 'superseded' ? $supersededByStickerId : null,
        ]);
        $sticker = $authorization->skip(fn () => $dbForProject->updateDocument('stickers', $stickerId, $changes));
        $groupId = $sticker->getAttribute('groupId');
        if (!empty($groupId)) {
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

        Activity::record(
            $dbForProject,
            $authorization,
            $user,
            'sticker',
            $stickerId,
            'sticker.status.update',
            [
                'status' => $status,
                'reason' => $reason,
                'supersededByStickerId' => $supersededByStickerId,
            ],
            $sticker->getPermissions(),
        );
        $queueForEvents->setParam('stickerId', $stickerId);
        $response->dynamic($sticker, Response::MODEL_STICKER);
    }
}
