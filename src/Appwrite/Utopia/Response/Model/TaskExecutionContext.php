<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;

class TaskExecutionContext extends Model
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
                'default' => 'task_execution',
                'example' => 'task_execution',
            ])
            ->addRule('task', [
                'type' => Response::MODEL_PROJECT_TASK,
                'description' => 'Task being executed.',
                'default' => [],
            ])
            ->addRule('parentTasks', [
                'type' => Response::MODEL_PROJECT_TASK,
                'description' => 'Ordered parent task chain.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('childTasks', [
                'type' => Response::MODEL_PROJECT_TASK,
                'description' => 'Direct child tasks.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('acceptanceCriteria', [
                'type' => self::TYPE_JSON,
                'description' => 'Acceptance criteria.',
                'default' => [],
            ])
            ->addRule('directStickers', [
                'type' => Response::MODEL_STICKER,
                'description' => 'Valid task Stickers.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('relevantGroupSummaries', [
                'type' => Response::MODEL_STICKER_GROUP_SUMMARY,
                'description' => 'Current related summaries.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('validConstraints', [
                'type' => Response::MODEL_STICKER,
                'description' => 'Valid constraints.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('validDecisions', [
                'type' => Response::MODEL_STICKER,
                'description' => 'Valid decisions.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('previousRuns', [
                'type' => Response::MODEL_AGENT_RUN,
                'description' => 'Previous agent runs.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('evidence', [
                'type' => Response::MODEL_EVIDENCE,
                'description' => 'Execution evidence.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('nextStatusActions', [
                'type' => self::TYPE_STRING,
                'description' => 'Legal next task statuses.',
                'default' => [],
                'array' => true,
            ])
            ->addRule('truncation', [
                'type' => self::TYPE_JSON,
                'description' => 'Fields that reached their context limit and may require a list API query.',
                'default' => [],
                'example' => ['directStickers' => false],
            ]);
    }

    public function getName(): string
    {
        return 'Task Execution Context';
    }

    public function getType(): string
    {
        return Response::MODEL_TASK_EXECUTION_CONTEXT;
    }
}
