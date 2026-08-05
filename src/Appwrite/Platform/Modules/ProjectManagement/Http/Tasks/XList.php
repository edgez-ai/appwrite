<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Tasks;

use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Database\Validator\Queries\ProjectTasks;
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
        return 'listProjectTasks';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/tasks')
            ->desc('List project tasks')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('listTasks', Response::MODEL_PROJECT_TASK_LIST))
            ->param('queries', [], new ProjectTasks(), 'Task queries.', true)
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
        $tasks = $dbForProject->find('projectTasks', $queries);
        $total = $includeTotal ? $dbForProject->count('projectTasks', $filters, APP_LIMIT_COUNT) : 0;
        $response->dynamic(new Document(['tasks' => $tasks, 'total' => $total]), Response::MODEL_PROJECT_TASK_LIST);
    }
}
