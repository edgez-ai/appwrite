<?php

namespace Appwrite\Platform\Modules\Devices\Http\Devices;

use Appwrite\Devices\DeviceId;
use Appwrite\Devices\DevicePermissions;
use Appwrite\Devices\Validator\Serial;
use Appwrite\Event\Event;
use Appwrite\Extend\Exception;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Database\Documents\User;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\Permissions;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Boolean;
use Utopia\Validator\JSON;
use Utopia\Validator\Text;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createDevice';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/devices')
            ->desc('Create device')
            ->groups(['api', 'devices'])
            ->label('scope', 'devices.write')
            ->label('resourceType', RESOURCE_TYPE_DEVICES)
            ->label('event', 'devices.[deviceId].create')
            ->label('audits.event', 'device.create')
            ->label('audits.resource', 'device/{response.$id}')
            ->label('sdk', new Method(
                namespace: 'devices',
                group: 'devices',
                name: 'createDevice',
                description: '/docs/references/devices/create-device.md',
                auth: [AuthType::ADMIN, AuthType::KEY, AuthType::SESSION, AuthType::JWT],
                responses: [
                    new SDKResponse(
                        code: Response::STATUS_CODE_CREATED,
                        model: Response::MODEL_DEVICE,
                    ),
                ],
            ))
            ->param('serial', '', new Serial(), 'Customer-defined serial number. Must be unique within the project.')
            ->param('name', '', new Text(128), 'Device name.')
            ->param('permissions', null, new Permissions(APP_LIMIT_ARRAY_PARAMS_SIZE, [Database::PERMISSION_READ, Database::PERMISSION_UPDATE, Database::PERMISSION_DELETE, Database::PERMISSION_WRITE]), 'Device permissions. By default, the current user receives read, update, and delete access.', true)
            ->param('enabled', true, new Boolean(true), 'Whether the device may authenticate with the MQTT broker.', true)
            ->param('metadata', [], new JSON(), 'Developer-defined device metadata.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $serial,
        string $name,
        ?array $permissions,
        bool $enabled,
        array $metadata,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $deviceId = DeviceId::generate();

        $device = new Document([
            '$id' => $deviceId,
            '$collection' => 'devices',
            'serial' => $serial,
            'name' => $name,
            'enabled' => $enabled,
            'status' => 'offline',
            'lastSeenAt' => null,
            'metadata' => $metadata,
            'search' => \implode(' ', [$deviceId, $serial, $name]),
        ]);

        (new DevicePermissions())->setOnCreate($device, $permissions, $user, $authorization);

        try {
            $device = $dbForProject->createDocument('devices', $device);
        } catch (Duplicate $error) {
            throw new Exception(Exception::DEVICE_ALREADY_EXISTS, previous: $error);
        }

        $queueForEvents->setParam('deviceId', $device->getId());

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic($device, Response::MODEL_DEVICE);
    }
}
