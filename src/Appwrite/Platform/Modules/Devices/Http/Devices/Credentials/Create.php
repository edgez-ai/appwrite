<?php

namespace Appwrite\Platform\Modules\Devices\Http\Devices\Credentials;

use Appwrite\Devices\Credentials;
use Appwrite\Devices\DevicePermissions;
use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Database\Documents\User;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\Datetime as DatetimeValidator;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Nullable;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createDeviceCredential';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/devices/:deviceId/credentials')
            ->desc('Create or rotate device credentials')
            ->groups(['api', 'devices'])
            ->label('scope', 'devices.write')
            ->label('resourceType', RESOURCE_TYPE_DEVICES)
            ->label('event', 'devices.[deviceId].credentials.[credentialId].create')
            ->label('audits.event', 'device.credentials.create')
            ->label('audits.resource', 'device/{request.deviceId}')
            ->label('sdk', new Method(
                namespace: 'devices',
                group: 'credentials',
                name: 'createCredential',
                description: '/docs/references/devices/create-credential.md',
                auth: [AuthType::ADMIN, AuthType::KEY, AuthType::SESSION, AuthType::JWT],
                responses: [new SDKResponse(code: Response::STATUS_CODE_CREATED, model: Response::MODEL_DEVICE_CREDENTIAL)],
            ))
            ->param('deviceId', '', new UID(), 'Device ID.')
            ->param('expire', null, new Nullable(new DatetimeValidator(requireDateInFuture: true)), 'Optional credential expiry time.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->inject('dbForPlatform')
            ->inject('project')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $deviceId,
        ?string $expire,
        Response $response,
        Database $dbForProject,
        Database $dbForPlatform,
        Document $project,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $device = $authorization->skip(fn () => $dbForProject->getDocument('devices', $deviceId));
        if ($device->isEmpty()) {
            throw new Exception(Exception::DEVICE_NOT_FOUND);
        }

        (new DevicePermissions())->assert($device, Database::PERMISSION_UPDATE, $user, $authorization);

        $credentials = new Credentials();
        $password = $credentials->generateSecret();
        $username = $credentials->getUsername($device->getAttribute('serial'));
        $clientId = $credentials->getClientId($deviceId);

        $route = $authorization->skip(fn () => $dbForPlatform->getDocument('deviceRoutes', $clientId));
        if ($route->isEmpty()) {
            $authorization->skip(fn () => $dbForPlatform->createDocument('deviceRoutes', new Document([
                '$id' => $clientId,
                '$collection' => 'deviceRoutes',
                '$permissions' => [],
                'projectId' => $project->getId(),
                'deviceId' => $deviceId,
                'serial' => $device->getAttribute('serial'),
            ])));
        } elseif (
            $route->getAttribute('projectId') !== $project->getId()
            || $route->getAttribute('deviceId') !== $deviceId
        ) {
            throw new Exception(Exception::DEVICE_ALREADY_EXISTS, 'The generated MQTT client ID is already registered.');
        }

        $credential = $authorization->skip(fn () => $dbForProject->findOne('deviceCredentials', [
            Query::equal('deviceInternalId', [$device->getSequence()]),
        ]));

        $values = new Document([
            'deviceInternalId' => $device->getSequence(),
            'deviceId' => $deviceId,
            'username' => $username,
            'clientId' => $clientId,
            'secretHash' => $credentials->hash($password),
            'enabled' => true,
            'expire' => $expire,
            'lastUsedAt' => null,
        ]);

        if ($credential->isEmpty()) {
            $values
                ->setAttribute('$id', ID::unique())
                ->setAttribute('$collection', 'deviceCredentials')
                ->setAttribute('$permissions', []);
            $credential = $authorization->skip(fn () => $dbForProject->createDocument('deviceCredentials', $values));
        } else {
            $credential = $authorization->skip(fn () => $dbForProject->updateDocument('deviceCredentials', $credential->getId(), $values));
        }

        $queueForEvents
            ->setParam('deviceId', $deviceId)
            ->setParam('credentialId', $credential->getId())
            ->setPayload([
                '$id' => $credential->getId(),
                'deviceId' => $deviceId,
                'username' => $username,
                'clientId' => $clientId,
                'expire' => $expire,
            ]);

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic(new Document([
                '$id' => $credential->getId(),
                '$createdAt' => $credential->getCreatedAt(),
                '$updatedAt' => $credential->getUpdatedAt(),
                'deviceId' => $deviceId,
                'username' => $username,
                'clientId' => $clientId,
                'password' => $password,
                'expire' => $expire,
            ]), Response::MODEL_DEVICE_CREDENTIAL);
    }
}
