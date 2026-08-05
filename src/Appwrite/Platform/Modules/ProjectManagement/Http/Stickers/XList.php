<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Stickers;

use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Validator\Queries\Stickers;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Query as QueryException;
use Utopia\Database\Query;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Boolean;
use Utopia\Validator\Text;

class XList extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'listStickers';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/stickers')
            ->desc('List Stickers')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_STICKERS)
            ->label('sdk', Sdk::method('listStickers', Response::MODEL_STICKER_LIST))
            ->param('queries', [], new Stickers(), 'Sticker queries.', true)
            ->param('search', '', new Text(256), 'Search term.', true)
            ->param('total', true, new Boolean(true), 'Calculate total.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->callback($this->action(...));
    }

    public function action(
        array $queries,
        string $search,
        bool $includeTotal,
        Response $response,
        Database $dbForProject,
    ): void {
        try {
            $queries = Query::parseQueries($queries);
        } catch (QueryException $error) {
            throw new Exception(Exception::GENERAL_QUERY_INVALID, $error->getMessage());
        }
        if ($search !== '') {
            $queries[] = Query::search('search', $search);
        }
        $filters = Query::groupByType($queries)['filters'];
        $response->dynamic(
            new Document([
                'stickers' => $dbForProject->find('stickers', $queries),
                'total' => $includeTotal ? $dbForProject->count('stickers', $filters, APP_LIMIT_COUNT) : 0,
            ]),
            Response::MODEL_STICKER_LIST,
        );
    }
}
