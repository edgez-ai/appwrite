<?php

namespace Appwrite\Platform\Modules\Devices\Services;

use Appwrite\Platform\Modules\Devices\Http\Devices\Create as CreateDevice;
use Appwrite\Platform\Modules\Devices\Http\Devices\Credentials\Create as CreateCredential;
use Appwrite\Platform\Modules\Devices\Http\Devices\Credentials\Delete as DeleteCredential;
use Appwrite\Platform\Modules\Devices\Http\Devices\Delete as DeleteDevice;
use Appwrite\Platform\Modules\Devices\Http\Devices\Get as GetDevice;
use Appwrite\Platform\Modules\Devices\Http\Devices\Sessions\Create as CreateSession;
use Appwrite\Platform\Modules\Devices\Http\Devices\Update as UpdateDevice;
use Appwrite\Platform\Modules\Devices\Http\Devices\XList as ListDevices;
use Utopia\Platform\Service;

class Http extends Service
{
    public function __construct()
    {
        $this->type = Service::TYPE_HTTP;

        $this->addAction(CreateDevice::getName(), new CreateDevice());
        $this->addAction(GetDevice::getName(), new GetDevice());
        $this->addAction(ListDevices::getName(), new ListDevices());
        $this->addAction(UpdateDevice::getName(), new UpdateDevice());
        $this->addAction(DeleteDevice::getName(), new DeleteDevice());
        $this->addAction(CreateCredential::getName(), new CreateCredential());
        $this->addAction(DeleteCredential::getName(), new DeleteCredential());
        $this->addAction(CreateSession::getName(), new CreateSession());
    }
}
