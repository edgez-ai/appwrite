<?php

namespace Appwrite\Platform\Modules\Devices\Http\Devices;

use Appwrite\Devices\DevicePermissions;
use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Database\Documents\User;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Delete extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'deleteDevice';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_DELETE)
            ->setHttpPath('/v1/devices/:deviceId')
            ->desc('Delete device')
            ->groups(['api', 'devices'])
            ->label('scope', 'devices.write')
            ->label('resourceType', RESOURCE_TYPE_DEVICES)
            ->label('event', 'devices.[deviceId].delete')
            ->label('audits.event', 'device.delete')
            ->label('audits.resource', 'device/{request.deviceId}')
            ->label('sdk', new Method(
                namespace: 'devices',
                group: 'devices',
                name: 'deleteDevice',
                description: '/docs/references/devices/delete-device.md',
                auth: [AuthType::ADMIN, AuthType::KEY, AuthType::SESSION, AuthType::JWT],
                responses: [new SDKResponse(code: Response::STATUS_CODE_NO_CONTENT, model: Response::MODEL_NONE)],
            ))
            ->param('deviceId', '', new UID(), 'Device ID.')
            ->inject('response')
            ->inject('dbForProject')
            ->inject('dbForPlatform')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $deviceId,
        Response $response,
        Database $dbForProject,
        Database $dbForPlatform,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $device = $authorization->skip(fn () => $dbForProject->getDocument('devices', $deviceId));
        if ($device->isEmpty()) {
            throw new Exception(Exception::DEVICE_NOT_FOUND);
        }

        (new DevicePermissions())->assert($device, Database::PERMISSION_DELETE, $user, $authorization);

        $authorization->skip(function () use ($dbForProject, $dbForPlatform, $device, $deviceId): void {
            $credential = $dbForProject->findOne('deviceCredentials', [
                Query::equal('deviceInternalId', [$device->getSequence()]),
            ]);
            if (!$credential->isEmpty()) {
                $dbForProject->deleteDocument('deviceCredentials', $credential->getId());
            }
            $route = $dbForPlatform->getDocument('deviceRoutes', $deviceId);
            if (!$route->isEmpty()) {
                $dbForPlatform->deleteDocument('deviceRoutes', $deviceId);
            }
            $dbForProject->deleteDocument('devices', $deviceId);
        });

        $queueForEvents->setParam('deviceId', $deviceId);
        $response->noContent();
    }
}
