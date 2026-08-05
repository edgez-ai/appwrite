<?php

namespace Appwrite\Platform\Modules\ProjectManagement\Http\Stickers;

use Appwrite\Extend\Exception;
use Appwrite\ProjectManagement\Sdk;
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
        return 'getSticker';
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_GET)
            ->setHttpPath('/v1/stickers/:stickerId')
            ->desc('Get Sticker')
            ->groups(['api', 'projectManagement'])
            ->label('scope', 'projectManagement.read')
            ->label('resourceType', RESOURCE_TYPE_STICKERS)
            ->label('sdk', Sdk::method('getSticker', Response::MODEL_STICKER))
            ->param('stickerId', '', new UID(), 'Sticker ID.')
            ->inject('response')
            ->inject('dbForProject')
            ->callback($this->action(...));
    }

    public function action(string $stickerId, Response $response, Database $dbForProject): void
    {
        $sticker = $dbForProject->getDocument('stickers', $stickerId);
        if ($sticker->isEmpty()) {
            throw new Exception(Exception::STICKER_NOT_FOUND);
        }
        $response->dynamic($sticker, Response::MODEL_STICKER);
    }
}
