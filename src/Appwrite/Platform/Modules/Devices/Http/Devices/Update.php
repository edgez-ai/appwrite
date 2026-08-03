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
use Utopia\Database\Document;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\Permissions;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Boolean;
use Utopia\Validator\JSON;
use Utopia\Validator\Nullable;
use Utopia\Validator\Text;

class Update extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'updateDevice';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_PATCH)
            ->setHttpPath('/v1/devices/:deviceId')
            ->desc('Update device')
            ->groups(['api', 'devices'])
            ->label('scope', 'devices.write')
            ->label('resourceType', RESOURCE_TYPE_DEVICES)
            ->label('event', 'devices.[deviceId].update')
            ->label('audits.event', 'device.update')
            ->label('audits.resource', 'device/{response.$id}')
            ->label('sdk', new Method(
                namespace: 'devices',
                group: 'devices',
                name: 'updateDevice',
                description: '/docs/references/devices/update-device.md',
                auth: [AuthType::ADMIN, AuthType::KEY, AuthType::SESSION, AuthType::JWT],
                responses: [new SDKResponse(code: Response::STATUS_CODE_OK, model: Response::MODEL_DEVICE)],
            ))
            ->param('deviceId', '', new UID(), 'Device ID.')
            ->param('name', null, new Nullable(new Text(128)), 'Device name.', true)
            ->param('permissions', null, new Nullable(new Permissions(APP_LIMIT_ARRAY_PARAMS_SIZE, [Database::PERMISSION_READ, Database::PERMISSION_UPDATE, Database::PERMISSION_DELETE, Database::PERMISSION_WRITE])), 'Device permissions.', true)
            ->param('enabled', null, new Nullable(new Boolean(true)), 'Whether the device may authenticate with the MQTT broker.', true)
            ->param('metadata', null, new Nullable(new JSON()), 'Developer-defined device metadata.', true)
            ->inject('response')
            ->inject('dbForProject')
            ->inject('user')
            ->inject('authorization')
            ->inject('queueForEvents')
            ->callback($this->action(...));
    }

    public function action(
        string $deviceId,
        ?string $name,
        ?array $permissions,
        ?bool $enabled,
        ?array $metadata,
        Response $response,
        Database $dbForProject,
        User $user,
        Authorization $authorization,
        Event $queueForEvents,
    ): void {
        $device = $authorization->skip(fn () => $dbForProject->getDocument('devices', $deviceId));
        if ($device->isEmpty()) {
            throw new Exception(Exception::DEVICE_NOT_FOUND);
        }

        $permissionHelper = new DevicePermissions();
        $permissionHelper->assert($device, Database::PERMISSION_UPDATE, $user, $authorization);

        $changes = new Document();
        if ($name !== null) {
            $changes->setAttribute('name', $name);
            $changes->setAttribute('search', \implode(' ', [$deviceId, $device->getAttribute('serial'), $name]));
        }
        if ($enabled !== null) {
            $changes->setAttribute('enabled', $enabled);
        }
        if ($metadata !== null) {
            $changes->setAttribute('metadata', $metadata);
        }
        $permissionHelper->setOnUpdate($changes, $permissions, $user, $authorization);

        $device = $authorization->skip(fn () => $dbForProject->updateDocument('devices', $deviceId, $changes));
        $queueForEvents->setParam('deviceId', $device->getId());

        $response->dynamic($device, Response::MODEL_DEVICE);
    }
}
