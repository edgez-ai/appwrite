<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Project\Context;

use Appwrite\ProjectManagement\Context;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Get extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'getProjectManagementContext';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/project/context')
            ->desc('Get deterministic project management context')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('getProjectContext', Response::MODEL_PROJECT_MANAGEMENT_CONTEXT))
            ->inject('response')
            ->inject('dbForProject')
            ->inject('project')
            ->callback($this->action(...));
    }

    public function action(Response $response, Database $dbForProject, Document $project): void
    {
        $response->dynamic(Context::forProject($dbForProject, $project), Response::MODEL_PROJECT_MANAGEMENT_CONTEXT);
    }
}
