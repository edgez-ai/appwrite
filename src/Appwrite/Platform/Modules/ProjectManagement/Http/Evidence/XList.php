<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Evidence;

use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Validator\Queries\Evidence;
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
        return 'listEvidence';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/evidence')
            ->desc('List execution Evidence')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_EVIDENCE)
            ->label('sdk', Sdk::method('listEvidence', Response::MODEL_EVIDENCE_LIST))
            ->param('queries', [], new Evidence(), 'Evidence queries.', true)
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
                'evidence' => $dbForProject->find('evidence', $queries),
                'total' => $dbForProject->count('evidence', $filters, APP_LIMIT_COUNT),
            ]),
            Response::MODEL_EVIDENCE_LIST,
        );
    }
}
