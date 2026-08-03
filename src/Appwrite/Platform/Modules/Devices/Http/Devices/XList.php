<?php

namespace Appwrite\Platform\Modules\Devices\Http\Devices;

use Appwrite\Extend\Exception;
use Appwrite\SDK\AuthType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Database\Validator\Queries\Devices;
use Appwrite\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Order as OrderException;
use Utopia\Database\Exception\Query as QueryException;
use Utopia\Database\Query;
use Utopia\Database\Validator\Query\Cursor;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Boolean;
use Utopia\Validator\Text;

class XList extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'listDevices';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/devices')
            ->desc('List devices')
            ->groups(['api', 'devices'])
            ->label('scope', 'devices.read')
            ->label('resourceType', RESOURCE_TYPE_DEVICES)
            ->label('sdk', new Method(
                namespace: 'devices',
                group: 'devices',
                name: 'listDevices',
                description: '/docs/references/devices/list-devices.md',
                auth: [AuthType::ADMIN, AuthType::KEY, AuthType::SESSION, AuthType::JWT],
                responses: [new SDKResponse(code: Response::STATUS_CODE_OK, model: Response::MODEL_DEVICE_LIST)],
            ))
            ->param('queries', [], new Devices(), 'Query strings. Filterable attributes: ' . \implode(', ', Devices::ALLOWED_ATTRIBUTES) . '.', true)
            ->param('search', '', new Text(256), 'Search term.', true)
            ->param('total', true, new Boolean(true), 'Whether to calculate the total number of matching devices.', true)
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

        if (!empty($search)) {
            $queries[] = Query::search('search', $search);
        }

        $cursorQueries = Query::getCursorQueries($queries, false);
        $cursor = \reset($cursorQueries);
        if ($cursor !== false) {
            $validator = new Cursor();
            if (!$validator->isValid($cursor)) {
                throw new Exception(Exception::GENERAL_QUERY_INVALID, $validator->getDescription());
            }

            $cursorDocument = $dbForProject->getDocument('devices', $cursor->getValue());
            if ($cursorDocument->isEmpty()) {
                throw new Exception(Exception::GENERAL_CURSOR_NOT_FOUND, "Device '{$cursor->getValue()}' for the cursor was not found.");
            }
            $cursor->setValue($cursorDocument);
        }

        $filters = Query::groupByType($queries)['filters'];

        try {
            $devices = $dbForProject->find('devices', $queries);
            $total = $includeTotal ? $dbForProject->count('devices', $filters, APP_LIMIT_COUNT) : 0;
        } catch (OrderException $error) {
            throw new Exception(Exception::DATABASE_QUERY_ORDER_NULL, $error->getMessage());
        } catch (QueryException $error) {
            throw new Exception(Exception::GENERAL_QUERY_INVALID, $error->getMessage());
        }

        $response->dynamic(new Document([
            'devices' => $devices,
            'total' => $total,
        ]), Response::MODEL_DEVICE_LIST);
    }
}
