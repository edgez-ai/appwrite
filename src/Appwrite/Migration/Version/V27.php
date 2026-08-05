<?php

namespace Appwrite\Migration\Version;

use Appwrite\Migration\Migration;
use Exception;
use Throwable;
use Utopia\Console;

class V27 extends Migration
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
            return;
        }

        Console::info('Creating project management collections');
        foreach (
            [
                'projectTasks',
                'stickers',
                'stickerGroups',
                'stickerGroupSummaries',
                'agentRuns',
                'evidence',
                'activityEvents',
                'chatBindings',
            ]
            as $collection
        ) {
            $this->createCollection($collection);
        }
    }
}
