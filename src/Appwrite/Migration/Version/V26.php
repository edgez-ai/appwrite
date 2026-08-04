<?php

namespace Appwrite\Migration\Version;

use Appwrite\Migration\Migration;
use Exception;
use Throwable;
use Utopia\Console;

class V26 extends Migration
{
    /**
     * @throws Throwable
     */
    public function execute(): void
    {
        $projectInternalId = $this->project->getSequence();

        if (empty($projectInternalId)) {
            throw new Exception('Project ID is null');
        }

        if ($projectInternalId === 'console') {
            Console::info('Creating device route collection');
            $this->createCollection('deviceRoutes');
            return;
        }

        Console::info('Creating device collections');
        $this->createCollection('devices');
        $this->createCollection('deviceCredentials');
        $this->createAttributesFromCollection($this->dbForProject, 'devices', ['mqttConnectedAt']);
    }
}
