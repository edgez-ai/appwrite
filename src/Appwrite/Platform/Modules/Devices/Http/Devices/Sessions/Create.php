<?php

namespace Appwrite\Platform\Modules\Devices\Http\Devices\Sessions;

use Appwrite\Devices\Credentials;
use Appwrite\Extend\Exception;
use Appwrite\Utopia\Request;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\DateTime;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\System\System;
use Utopia\Validator\Text;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createDeviceSession';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/devices/sessions')
            ->desc('Authenticate an MQTT device session')
            ->groups(['api', 'devices', 'internal'])
            ->label('scope', 'public')
            ->label('resourceType', RESOURCE_TYPE_DEVICES)
            ->param('username', '', new Text(Database::LENGTH_KEY), 'MQTT username.')
            ->param('password', '', new Text(512), 'MQTT password.')
            ->param('clientid', '', new Text(Database::LENGTH_KEY), 'MQTT client ID.')
            ->inject('request')
            ->inject('response')
            ->inject('dbForPlatform')
            ->inject('getProjectDB')
            ->inject('authorization')
            ->callback($this->action(...));
    }

    public function action(
        string $username,
        string $password,
        string $clientId,
        Request $request,
        Response $response,
        Database $dbForPlatform,
        callable $getProjectDB,
        Authorization $authorization,
    ): void {
        $expectedSecret = System::getEnv('_APP_DEVICES_EMQX_SECRET', '');
        $providedSecret = $request->getHeaderLine('x-appwrite-emqx-secret', '');

        if (empty($expectedSecret) || empty($providedSecret) || !\hash_equals($expectedSecret, $providedSecret)) {
            throw new Exception(Exception::GENERAL_ACCESS_FORBIDDEN, 'Invalid EMQX authentication secret.');
        }

        $route = $authorization->skip(fn () => $dbForPlatform->getDocument('deviceRoutes', $clientId));
        if ($route->isEmpty() || $route->getAttribute('deviceId') !== $clientId) {
            $this->deny($response);
            return;
        }

        $projectId = $route->getAttribute('projectId');
        $project = $authorization->skip(fn () => $dbForPlatform->getDocument('projects', $projectId));
        if ($project->isEmpty()) {
            $this->deny($response);
            return;
        }

        $dbForProject = $getProjectDB($project);
        $deviceId = $route->getAttribute('deviceId');
        [$device, $credential] = $authorization->skip(function () use ($dbForProject, $deviceId): array {
            $device = $dbForProject->getDocument('devices', $deviceId);
            if ($device->isEmpty()) {
                return [$device, new Document()];
            }

            $credential = $dbForProject->findOne('deviceCredentials', [
                Query::equal('deviceInternalId', [$device->getSequence()]),
            ]);

            return [$device, $credential];
        });

        $credentials = new Credentials();
        $serial = $device->getAttribute('serial', '');
        $expire = $credential->getAttribute('expire');
        $expired = !empty($expire) && DateTime::formatTz($expire) <= DateTime::formatTz(DateTime::now());

        if (
            $device->isEmpty()
            || $credential->isEmpty()
            || !$device->getAttribute('enabled', true)
            || !$credential->getAttribute('enabled', true)
            || $expired
            || $route->getAttribute('serial') !== $serial
            || $username !== $credentials->getUsername($serial)
            || $clientId !== $credentials->getClientId($deviceId)
            || $credential->getAttribute('username') !== $username
            || $credential->getAttribute('clientId') !== $clientId
            || !$credentials->verify($password, $credential->getAttribute('secretHash', ''))
        ) {
            $this->deny($response);
            return;
        }

        $authorization->skip(function () use ($dbForProject, $credential): void {
            $dbForProject->updateDocument('deviceCredentials', $credential->getId(), new Document([
                'lastUsedAt' => DateTime::now(),
            ]));
        });

        $payload = [
            'result' => 'allow',
            'is_superuser' => false,
            'client_attrs' => [
                'projectId' => $projectId,
                'deviceId' => $deviceId,
            ],
            'acl' => $credentials->getAcl($projectId, $serial),
        ];

        if (!empty($expire)) {
            $payload['expire_at'] = (new \DateTime($expire))->getTimestamp();
        }

        $response->json($payload);
    }

    private function deny(Response $response): void
    {
        $response->json([
            'result' => 'deny',
            'is_superuser' => false,
        ]);
    }
}
