<?php

namespace Appwrite\ProjectManagement;

use Appwrite\SDK\AuthType;
use Appwrite\SDK\ContentType;
use Appwrite\SDK\Method;
use Appwrite\SDK\Response as SDKResponse;
use Appwrite\Utopia\Response;

class Sdk
{
    public static function method(string $name, string $model, int $code = Response::STATUS_CODE_OK): Method
    {
        $responses = [new SDKResponse(code: $code, model: $model)];
        if ($code === Response::STATUS_CODE_CREATED) {
            $responses[] = new SDKResponse(code: Response::STATUS_CODE_OK, model: $model);
        }

        return new Method(
            namespace: 'projectManagement',
            group: 'projectManagement',
            name: $name,
            description: '/docs/references/project-management/README.md',
            auth: [AuthType::ADMIN, AuthType::KEY, AuthType::SESSION, AuthType::JWT],
            responses: $responses,
            contentType: $code === Response::STATUS_CODE_NOCONTENT ? ContentType::NONE : ContentType::JSON,
        );
    }
}
