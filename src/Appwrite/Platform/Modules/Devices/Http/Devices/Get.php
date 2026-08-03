<?php

namespace Appwrite\Platform\Modules\Devices\Http\Devices;

use Appwrite\Extend\Exception;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Validator\UID;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Get extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'getDevice';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/devices/:deviceId')
            ->desc('Get device')
            ->groups(['api', 'devices'])
            ->label('scope', 'devices.read')
            ->label('resourceType', RESOURCE_TYPE_DEVICES)
            ->label('sdk', new Method(
                namespace: 'devices',
                group: 'devices',
                name: 'getDevice',
                description: '/docs/references/devices/get-device.md',
                auth: [AuthType::ADMIN, AuthType::KEY, AuthType::SESSION, AuthType::JWT],
                responses: [new SDKResponse(code: Response::STATUS_CODE_OK, model: Response::MODEL_DEVICE)],
            ))
            ->param('deviceId', '', new UID(), 'Device ID.')
            ->inject('response')
            ->inject('dbForProject')
            ->callback($this->action(...));
    }

    public function action(string $deviceId, Response $response, Database $dbForProject): void
    {
        $device = $dbForProject->getDocument('devices', $deviceId);

        if ($device->isEmpty()) {
            throw new Exception(Exception::DEVICE_NOT_FOUND);
        }

        $response->dynamic($device, Response::MODEL_DEVICE);
    }
}
