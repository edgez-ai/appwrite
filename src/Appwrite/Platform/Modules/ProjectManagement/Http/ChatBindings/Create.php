<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\ChatBindings;

use Appwrite\Event\Event;
use Appwrite\ProjectManagement\ChatBindingKey;
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
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createChatBinding';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/chat-bindings')
            ->desc('Bind a chat conversation to this Appwrite project')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_CHAT_BINDINGS)
            ->label(
                'sdk',
                Sdk::method('createChatBinding', Response::MODEL_CHAT_BINDING, Response::STATUS_CODE_CREATED),
            )
            ->label('event', 'chatBindings.[bindingId].create')
            ->param('bindingId', 'unique()', new CustomId(), 'Binding ID or `unique()`.')
            ->param('provider', '', new WhiteList(['slack', 'teams', 'wecom']), 'Chat provider.')
            ->param('externalTenantId', '', new Text(255), 'Provider tenant/workspace ID.')
            ->param('conversationId', '', new Text(255), 'Channel or conversation ID.')
            ->param('threadId', '', new Text(255), 'Optional thread ID.', true)
            ->param(
                'triggerMode',
                'mention_only',
                new WhiteList(['mention_only', 'all_messages', 'commands_only']),
                'Agent trigger mode.',
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
                'Binding permissions.',
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
        string $bindingId,
        string $provider,
        string $externalTenantId,
        string $conversationId,
        string $threadId,
        string $triggerMode,
        ?array $permissions,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $bindingId = $bindingId === 'unique()' ? ID::unique() : $bindingId;
        $createHash = Idempotency::hash([
            'provider' => $provider,
            'externalTenantId' => $externalTenantId,
            'conversationId' => $conversationId,
            'threadId' => $threadId,
            'triggerMode' => $triggerMode,
            'permissions' => $permissions,
        ]);
        $binding = new Document([
            '$id' => $bindingId,
            '$collection' => 'chatBindings',
            'provider' => $provider,
            'externalTenantId' => $externalTenantId,
            'conversationId' => $conversationId,
            'threadId' => $threadId,
            'triggerMode' => $triggerMode,
            'bindingKey' => ChatBindingKey::generate($provider, $externalTenantId, $conversationId, $threadId),
            'createHash' => $createHash,
        ]);
        (new ResourcePermissions())->setOnCreate($binding, $permissions, $user, $authorization);
        $created = true;
        try {
            $binding = $authorization->skip(fn () => $dbForProject->createDocument('chatBindings', $binding));
        } catch (Duplicate) {
            $created = false;
            $binding = Idempotency::resolveCreateRetry($dbForProject, $authorization, 'chatBindings', $bindingId, [
                'createHash' => $createHash,
            ]);
        }
        if ($created) {
            $queueForEvents->setParam('bindingId', $bindingId);
        }
        $response
            ->setStatusCode($created ? Response::STATUS_CODE_CREATED : Response::STATUS_CODE_OK)
            ->dynamic($binding, Response::MODEL_CHAT_BINDING);
    }
}
