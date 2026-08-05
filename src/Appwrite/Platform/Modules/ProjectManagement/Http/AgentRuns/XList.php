<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\AgentRuns;

use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Validator\Queries\AgentRuns;
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
        return 'listAgentRuns';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/agent-runs')
            ->desc('List Agent Runs')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_AGENT_RUNS)
            ->label('sdk', Sdk::method('listAgentRuns', Response::MODEL_AGENT_RUN_LIST))
            ->param('queries', [], new AgentRuns(), 'Agent Run queries.', true)
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
                'runs' => $dbForProject->find('agentRuns', $queries),
                'total' => $dbForProject->count('agentRuns', $filters, APP_LIMIT_COUNT),
            ]),
            Response::MODEL_AGENT_RUN_LIST,
        );
    }
}
