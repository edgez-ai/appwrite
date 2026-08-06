<?php

namespace Appwrite\Platform\Modules\VCS\Http\Installations;

use Appwrite\Extend\Exception;
use Appwrite\Platform\Action;
use Appwrite\Platform\Permission as AppwritePermission;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Query;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Text;

class Create extends Action
{
    use HTTP;
    use AppwritePermission;

    public static function getName()
    {
        return 'createInstallation';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/vcs/installations')
            ->desc('Link existing installation')
            ->groups(['api', 'vcs'])
            ->label('scope', 'vcs.write')
            ->label('resourceType', RESOURCE_TYPE_VCS)
            ->label('sdk', new Method(
                namespace: 'vcs',
                group: 'installations',
                name: 'createInstallation',
                description: '/docs/references/vcs/create-installation.md',
                auth: [AuthType::ADMIN],
                responses: [
                    new SDKResponse(
                        code: Response::STATUS_CODE_CREATED,
                        model: Response::MODEL_INSTALLATION,
                    )
                ]
            ))
            ->param('sourceInstallationId', '', new Text(256), 'Existing installation ID to link.')
            ->inject('response')
            ->inject('project')
            ->inject('dbForPlatform')
            ->callback($this->action(...));
    }

    public function action(
        string $sourceInstallationId,
        Response $response,
        Document $project,
        Database $dbForPlatform,
    ): void {
        $source = $dbForPlatform->getDocument('installations', $sourceInstallationId);

        if ($source->isEmpty()) {
            throw new Exception(Exception::INSTALLATION_NOT_FOUND);
        }

        $projectInternalId = $project->getSequence();
        $existing = $dbForPlatform->findOne('installations', [
            Query::equal('providerInstallationId', [$source->getAttribute('providerInstallationId')]),
            Query::equal('projectInternalId', [$projectInternalId]),
        ]);

        if (!$existing->isEmpty()) {
            $response->dynamic($existing, Response::MODEL_INSTALLATION);
            return;
        }

        $installation = new Document([
            '$id' => ID::unique(),
            '$permissions' => $this->getPermissions(
                $project->getAttribute('teamId', ''),
                $project->getId(),
            ),
            'providerInstallationId' => $source->getAttribute('providerInstallationId'),
            'projectId' => $project->getId(),
            'projectInternalId' => $projectInternalId,
            'provider' => $source->getAttribute('provider'),
            'organization' => $source->getAttribute('organization'),
            'personal' => $source->getAttribute('personal', false),
            'personalRefreshToken' => $source->getAttribute('personalRefreshToken'),
            'personalAccessToken' => $source->getAttribute('personalAccessToken'),
            'personalAccessTokenExpiry' => $source->getAttribute('personalAccessTokenExpiry'),
        ]);

        $installation = $dbForPlatform->createDocument('installations', $installation);

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic($installation, Response::MODEL_INSTALLATION);
    }
}
