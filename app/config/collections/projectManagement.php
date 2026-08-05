<?php

use Utopia\Database\Database;
use Utopia\Database\Helpers\ID;

$string = static fn (
    string $id,
    int $size = Database::LENGTH_KEY,
    bool $required = false,
    mixed $default = null,
    bool $array = false,
): array => [
    '$id' => ID::custom($id),
    'type' => Database::VAR_STRING,
    'size' => $size,
    'signed' => true,
    'required' => $required,
    'default' => $default,
    'array' => $array,
    'filters' => [],
];

$text = static fn (string $id, bool $required = false, mixed $default = null, array $filters = []): array => [
    '$id' => ID::custom($id),
    'type' => Database::VAR_TEXT,
    'size' => 65535,
    'signed' => true,
    'required' => $required,
    'default' => $default,
    'array' => false,
    'filters' => $filters,
];

$integer = static fn (string $id, int $default = 0): array => [
    '$id' => ID::custom($id),
    'type' => Database::VAR_INTEGER,
    'size' => 8,
    'signed' => true,
    'required' => false,
    'default' => $default,
    'array' => false,
    'filters' => [],
];

$datetime = static fn (string $id): array => [
    '$id' => ID::custom($id),
    'type' => Database::VAR_DATETIME,
    'size' => 0,
    'signed' => false,
    'required' => false,
    'default' => null,
    'array' => false,
    'filters' => ['datetime'],
];

$json = static fn (string $id, mixed $default = new stdClass()): array => $text(
    $id,
    default: $default,
    filters: ['json'],
);

$key = static fn (string $id, array $attributes, array $lengths = []): array => [
    '$id' => ID::custom($id),
    'type' => Database::INDEX_KEY,
    'attributes' => $attributes,
    'lengths' => $lengths,
    'orders' => \array_fill(0, \count($attributes), Database::ORDER_ASC),
];

$unique = static fn (string $id, array $attributes, array $lengths = []): array => [
    '$id' => ID::custom($id),
    'type' => Database::INDEX_UNIQUE,
    'attributes' => $attributes,
    'lengths' => $lengths,
    'orders' => \array_fill(0, \count($attributes), Database::ORDER_ASC),
];

$search = static fn (): array => [
    '$id' => ID::custom('_fulltext_search'),
    'type' => Database::INDEX_FULLTEXT,
    'attributes' => ['search'],
    'lengths' => [],
    'orders' => [],
];

return [
    'projectTasks' => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom('projectTasks'),
        'name' => 'Project Tasks',
        'attributes' => [
            $string('parentTaskId'),
            $string('title', 256, true),
            $text('description', default: ''),
            $string('status', 32, default: 'draft'),
            $string('priority', 16, default: 'medium'),
            $string('assigneeType', 32, default: 'human'),
            $string('assigneeId'),
            $string('claimedByType', 32),
            $string('claimedById'),
            $datetime('claimedAt'),
            $string('agentRunId'),
            $json('acceptanceCriteria', []),
            $integer('sortOrder'),
            $string('createdBy'),
            $string('createHash', 64, true),
            $string('search', 16384),
        ],
        'indexes' => [
            $key('_key_parentTaskId', ['parentTaskId']),
            $key('_key_parent_sortOrder', ['parentTaskId', 'sortOrder']),
            $key('_key_status', ['status'], [32]),
            $key('_key_assignee', ['assigneeType', 'assigneeId'], [32, Database::LENGTH_KEY]),
            $key('_key_sortOrder', ['sortOrder']),
            $search(),
        ],
    ],
    'stickers' => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom('stickers'),
        'name' => 'Stickers',
        'attributes' => [
            $string('taskId'),
            $string('groupId'),
            $string('type', 32, true),
            $string('title', 256),
            $text('content', true),
            $string('status', 16, default: 'valid'),
            $string('sourceType', 32, default: 'manual'),
            $string('sourceId'),
            $string('createdByType', 32, default: 'user'),
            $string('createdById'),
            $string('statusChangedBy'),
            $datetime('statusChangedAt'),
            $text('statusReason', default: ''),
            $string('supersedesStickerId'),
            $string('supersededByStickerId'),
            $string('createHash', 64, true),
            $string('search', 16384),
        ],
        'indexes' => [
            $key('_key_taskId', ['taskId']),
            $key('_key_groupId', ['groupId']),
            $key('_key_status', ['status'], [16]),
            $key('_key_type', ['type'], [32]),
            $key('_key_task_status', ['taskId', 'status'], [Database::LENGTH_KEY, 16]),
            $key('_key_group_status', ['groupId', 'status'], [Database::LENGTH_KEY, 16]),
            $search(),
        ],
    ],
    'stickerGroups' => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom('stickerGroups'),
        'name' => 'Sticker Groups',
        'attributes' => [
            $string('taskId'),
            $string('title', 256, true),
            $text('description', default: ''),
            $string('currentSummaryId'),
            $string('summaryStatus', 16, default: 'pending'),
            $datetime('lastStickerAt'),
            $string('createdBy'),
            $string('createHash', 64, true),
            $string('search', 16384),
        ],
        'indexes' => [$key('_key_taskId', ['taskId']), $key('_key_summaryStatus', ['summaryStatus'], [16]), $search()],
    ],
    'stickerGroupSummaries' => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom('stickerGroupSummaries'),
        'name' => 'Sticker Group Summaries',
        'attributes' => [
            $string('groupId', required: true),
            $integer('version', 1),
            $text('content', true),
            $json('structuredContent'),
            $string('basedOnStickerIds', array: true, default: []),
            $string('model', 128, true),
            $string('promptVersion', 64, true),
            $string('generationStatus', 16, default: 'ready'),
            $text('generationError', default: ''),
            $string('createHash', 64, true),
        ],
        'indexes' => [
            $unique('_unique_group_version', ['groupId', 'version'], [Database::LENGTH_KEY]),
            $key('_key_groupId', ['groupId']),
        ],
    ],
    'agentRuns' => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom('agentRuns'),
        'name' => 'Agent Runs',
        'attributes' => [
            $string('taskId', required: true),
            $string('agentType', 32, true),
            $string('status', 32, default: 'queued'),
            $string('workspaceId'),
            $string('codexThreadId'),
            $integer('attempt', 1),
            $json('inputContextSnapshot'),
            $text('resultSummary', default: ''),
            $datetime('startedAt'),
            $datetime('completedAt'),
            $string('createHash', 64, true),
            $string('search', 16384),
        ],
        'indexes' => [$key('_key_taskId', ['taskId']), $key('_key_status', ['status'], [32]), $search()],
    ],
    'evidence' => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom('evidence'),
        'name' => 'Evidence',
        'attributes' => [
            $string('taskId', required: true),
            $string('agentRunId', required: true),
            $string('type', 32, true),
            $string('title', 256),
            $string('url', 2048),
            $string('storageFileId'),
            $json('metadata'),
            $string('createHash', 64, true),
        ],
        'indexes' => [
            $key('_key_taskId', ['taskId']),
            $key('_key_agentRunId', ['agentRunId']),
            $key('_key_type', ['type'], [32]),
        ],
    ],
    'activityEvents' => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom('activityEvents'),
        'name' => 'Activity Events',
        'attributes' => [
            $string('entityType', 32, true),
            $string('entityId', required: true),
            $string('eventType', 128, true),
            $string('actorType', 32, default: 'user'),
            $string('actorId'),
            $json('payload'),
        ],
        'indexes' => [
            $key('_key_entity', ['entityType', 'entityId'], [32, Database::LENGTH_KEY]),
            $key('_key_eventType', ['eventType'], [128]),
        ],
    ],
    'chatBindings' => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom('chatBindings'),
        'name' => 'Chat Bindings',
        'attributes' => [
            $string('provider', 16, true),
            $string('externalTenantId', required: true),
            $string('conversationId', required: true),
            $string('threadId', default: ''),
            $string('triggerMode', 32, default: 'mention_only'),
            $string('bindingKey', 64, true),
            $string('createHash', 64, true),
        ],
        'indexes' => [$unique('_unique_bindingKey', ['bindingKey'], [64]), $key('_key_provider', ['provider'], [16])],
    ],
];
