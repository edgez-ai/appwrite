<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;

class ProjectManagementContext extends Model
{
    public function __construct()
    {
        $this->addRule('schemaVersion', [
            'type' => self::TYPE_STRING,
            'description' => 'Context schema version.',
            'default' => '',
            'example' => '2026-08-06',
        ])
            ->addRule('contextType', [
                'type' => self::TYPE_STRING,
                'description' => 'Context type.',
                'default' => 'project_management',
                'example' => 'project_management',
            ])
            ->addRule('project', [
                'type' => Response::MODEL_PROJECT,
                'description' => 'Current Appwrite project.',
                'default' => [],
            ])
            ->addRule('activeTasks', [
                'type' => Response::MODEL_PROJECT_TASK,
                'description' => 'Tasks requiring attention.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('recentTasks', [
                'type' => Response::MODEL_PROJECT_TASK,
                'description' => 'Recently changed tasks.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('projectStickers', [
                'type' => Response::MODEL_STICKER,
                'description' => 'Valid project knowledge.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('stickerGroupSummaries', [
                'type' => Response::MODEL_STICKER_GROUP_SUMMARY,
                'description' => 'Current group summaries.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('recentActivity', [
                'type' => Response::MODEL_ACTIVITY_EVENT,
                'description' => 'Recent domain activity.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('repositories', [
                'type' => self::TYPE_JSON,
                'description' => 'Resolved repositories.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('workspaces', [
                'type' => self::TYPE_JSON,
                'description' => 'Resolved workspaces.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('truncation', [
                'type' => self::TYPE_JSON,
                'description' => 'Fields that reached their context limit and may require a list API query.',
                'default' => [],
                'example' => ['activeTasks' => false],
            ]);
    }

    public function getName(): string
    {
        return 'Project Management Context';
    }

    public function getType(): string
    {
        return Response::MODEL_PROJECT_MANAGEMENT_CONTEXT;
    }
}
