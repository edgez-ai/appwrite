<?php

namespace Appwrite\Utopia\Database\Validator\Queries;

class Devices extends Base
{
    public const ALLOWED_ATTRIBUTES = [
        'name',
        'serial',
        'enabled',
        'status',
        'lastSeenAt',
    ];

    public function __construct()
    {
        parent::__construct('devices', self::ALLOWED_ATTRIBUTES);
    }
}
