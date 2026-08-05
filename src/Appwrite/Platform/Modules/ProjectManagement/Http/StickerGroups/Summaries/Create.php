<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\StickerGroups\Summaries;

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
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\ArrayList;
use Utopia\Validator\JSON;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createStickerGroupSummary';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/sticker-groups/:groupId/summaries')
            ->desc('Create an immutable Sticker Group summary version')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_STICKER_GROUPS)
            ->label(
                'sdk',
                Sdk::method(
                    'createStickerGroupSummary',
                    Response::MODEL_STICKER_GROUP_SUMMARY,
                    Response::STATUS_CODE_CREATED,
                ),
            )
            ->label('event', 'stickerGroups.[groupId].summaries.[summaryId].create')
            ->param('groupId', '', new UID(), 'Sticker Group ID.')
            ->param('summaryId', 'unique()', new CustomId(), 'Summary ID or `unique()`. Reuse it when retrying.')
            ->param('content', '', new Text(65535), 'Rendered summary.')
            ->param('structuredContent', [], new JSON(), 'Structured summary.', true)
            ->param(
                'basedOnStickerIds',
                [],
                new ArrayList(new UID(), APP_LIMIT_ARRAY_PARAMS_SIZE),
                'Sticker IDs used.',
                true,
            )
            ->param('model', '', new Text(128), 'Generating model identifier.')
            ->param('promptVersion', '', new Text(64), 'Prompt version.')
            ->param('generationStatus', 'ready', new WhiteList(['ready', 'failed']), 'Generation status.', true)
            ->param('generationError', '', new Text(65535), 'Generation error.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $groupId,
        string $summaryId,
        string $content,
        array $structuredContent,
        array $basedOnStickerIds,
        string $model,
        string $promptVersion,
        string $generationStatus,
        string $generationError,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $group = $authorization->skip(fn () => $dbForProject->getDocument('stickerGroups', $groupId));
        if ($group->isEmpty()) {
            throw new Exception(Exception::STICKER_GROUP_NOT_FOUND);
        }
        (new ResourcePermissions())->assert($group, Database::PERMISSION_UPDATE, $user, $authorization);
        $createHash = Idempotency::hash([
            'groupId' => $groupId,
            'content' => $content,
            'structuredContent' => $structuredContent,
            'basedOnStickerIds' => $basedOnStickerIds,
            'model' => $model,
            'promptVersion' => $promptVersion,
            'generationStatus' => $generationStatus,
            'generationError' => $generationError,
        ]);
        $latest = $authorization->skip(
            fn () => $dbForProject->findOne('stickerGroupSummaries', [
                Query::equal('groupId', [$groupId]),
                Query::orderDesc('version'),
            ]),
        );
        $version = $latest->isEmpty() ? 1 : $latest->getAttribute('version', 0) + 1;
        $summaryId = $summaryId === 'unique()' ? ID::unique() : $summaryId;
        $created = true;
        try {
            $summary = $authorization->skip(
                fn () => $dbForProject->createDocument(
                    'stickerGroupSummaries',
                    new Document([
                        '$id' => $summaryId,
                        '$collection' => 'stickerGroupSummaries',
                        '$permissions' => $group->getPermissions(),
                        'groupId' => $groupId,
                        'version' => $version,
                        'content' => $content,
                        'structuredContent' => $structuredContent,
                        'basedOnStickerIds' => $basedOnStickerIds,
                        'model' => $model,
                        'promptVersion' => $promptVersion,
                        'generationStatus' => $generationStatus,
                        'generationError' => $generationError,
                        'createHash' => $createHash,
                    ]),
                ),
            );
        } catch (Duplicate) {
            $created = false;
            $summary = Idempotency::resolveCreateRetry(
                $dbForProject,
                $authorization,
                'stickerGroupSummaries',
                $summaryId,
                ['createHash' => $createHash],
            );
        }
        if ($created) {
            $authorization->skip(
                fn () => $dbForProject->updateDocument(
                    'stickerGroups',
                    $groupId,
                    new Document([
                        'currentSummaryId' => $summaryId,
                        'summaryStatus' => $generationStatus,
                    ]),
                ),
            );
            $queueForEvents->setParam('groupId', $groupId)->setParam('summaryId', $summaryId);
        }
        $response
            ->setStatusCode($created ? Response::STATUS_CODE_CREATED : Response::STATUS_CODE_OK)
            ->dynamic($summary, Response::MODEL_STICKER_GROUP_SUMMARY);
    }
}
