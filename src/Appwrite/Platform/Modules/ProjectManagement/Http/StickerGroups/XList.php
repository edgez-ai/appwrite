<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\StickerGroups;

use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Validator\Queries\StickerGroups;
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
        return 'listStickerGroups';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/sticker-groups')
            ->desc('List Sticker Groups')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_STICKER_GROUPS)
            ->label('sdk', Sdk::method('listStickerGroups', Response::MODEL_STICKER_GROUP_LIST))
            ->param('queries', [], new StickerGroups(), 'Sticker Group queries.', true)
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
        $filters = Query::groupByType($queries)['filters'];
        $response->dynamic(
            new Document([
                'groups' => $dbForProject->find('stickerGroups', $queries),
                'total' => $dbForProject->count('stickerGroups', $filters, APP_LIMIT_COUNT),
            ]),
            Response::MODEL_STICKER_GROUP_LIST,
        );
    }
}
