<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\ChatBindings;

use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Validator\Queries\ChatBindings;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Query as QueryException;
use Utopia\Database\Query;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class XList extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'listChatBindings';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/chat-bindings')
            ->desc('List chat bindings')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_CHAT_BINDINGS)
            ->label('sdk', Sdk::method('listChatBindings', Response::MODEL_CHAT_BINDING_LIST))
            ->param('queries', [], new ChatBindings(), 'Binding queries.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->callback($this->action(...));
    }

    public function action(array $queries, Response $response, Database $dbForProject): void
    {
        try {
            $queries = Query::parseQueries($queries);
        } catch (QueryException $error) {
            throw new Exception(Exception::GENERAL_QUERY_INVALID, $error->getMessage());
        }
        $response->dynamic(
            new Document([
                'bindings' => $dbForProject->find('chatBindings', $queries),
                'total' => $dbForProject->count(
                    'chatBindings',
                    Query::groupByType($queries)['filters'],
                    APP_LIMIT_COUNT,
                ),
            ]),
            Response::MODEL_CHAT_BINDING_LIST,
        );
    }
}
