<?php

namespace Appwrite\Utopia\Database\Validator\Queries;

class Stickers extends Base
{
    public const ALLOWED_ATTRIBUTES = ['taskId', 'groupId', 'type', 'status', 'sourceType', 'createdByType'];

    public function __construct()
    {
        parent::__construct('stickers', self::ALLOWED_ATTRIBUTES);
    }
}
