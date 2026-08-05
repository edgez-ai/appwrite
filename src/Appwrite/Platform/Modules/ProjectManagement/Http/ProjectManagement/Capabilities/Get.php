<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\ProjectManagement\Capabilities;

use Appwrite\ProjectManagement\Capabilities;
use Appwrite\ProjectManagement\Sdk;
use Appwrite\Utopia\Response;
use Utopia\Database\Document;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Get extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'getProjectManagementCapabilities';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/project-management/capabilities')
            ->desc('Get the machine-readable project management agent contract')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_PROJECT_TASKS)
            ->label('sdk', Sdk::method('getCapabilities', Response::MODEL_PROJECT_MANAGEMENT_CAPABILITIES))
            ->inject('response')
            ->callback($this->action(...));
    }

    public function action(Response $response): void
    {
        $response->dynamic(
            new Document(Capabilities::get()),
            Response::MODEL_PROJECT_MANAGEMENT_CAPABILITIES,
        );
    }
}
