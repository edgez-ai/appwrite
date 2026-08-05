<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\ChatBindings;

use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Permissions as ResourcePermissions;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Documents\User;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Delete extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'deleteChatBinding';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_DELETE)
            ->setHttpPath('/v1/chat-bindings/:bindingId')
            ->desc('Delete chat binding')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.write')
            ->label('resourceType', RESOURCE_TYPE_CHAT_BINDINGS)
            ->label('sdk', Sdk::method('deleteChatBinding', Response::MODEL_NONE, Response::STATUS_CODE_NOCONTENT))
            ->label('event', 'chatBindings.[bindingId].delete')
            ->param('bindingId', '', new UID(), 'Binding ID.')
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $bindingId,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $binding = $authorization->skip(fn () => $dbForProject->getDocument('chatBindings', $bindingId));
        if ($binding->isEmpty()) {
            throw new Exception(Exception::CHAT_BINDING_NOT_FOUND);
        }
        (new ResourcePermissions())->assert($binding, Database::PERMISSION_DELETE, $user, $authorization);
        $authorization->skip(fn () => $dbForProject->deleteDocument('chatBindings', $bindingId));
        $queueForEvents->setParam('bindingId', $bindingId);
        $response->noContent();
    }
}
