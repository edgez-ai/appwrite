<?php

namespace Appwrite\Platform\Modules\Devices;

use Appwrite\Platform\Modules\Devices\Services\Http;
use Utopia\Platform;

class Module extends Platform\Module
{
    public function __construct()
    {
        $this->addService('http', new Http());
    }
}
