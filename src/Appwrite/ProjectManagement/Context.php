<?php

namespace Appwrite\ProjectManagement;

use Appwrite\Extend\Exception;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;

class Context
{
    public static function forTask(Database $database, string $taskId): Document
    {
        $task = $database->getDocument('projectTasks', $taskId);
        if ($task->isEmpty()) {
            throw new Exception(Exception::PROJECT_TASK_NOT_FOUND);
        }

        $ancestors = [];
        $visited = [$taskId => true];
        $parentTaskId = (string) $task->getAttribute('parentTaskId', '');
        while (!empty($parentTaskId) && \count($ancestors) < 32) {
            if (isset($visited[$parentTaskId])) {
                throw new Exception(Exception::GENERAL_SERVER_ERROR, 'A cycle exists in the task hierarchy.');
            }
            $visited[$parentTaskId] = true;
            $parent = $database->getDocument('projectTasks', $parentTaskId);
            if ($parent->isEmpty()) {
                break;
            }
            $ancestors[] = $parent;
            $parentTaskId = (string) $parent->getAttribute('parentTaskId', '');
        }
        $ancestors = \array_reverse($ancestors);
        $children = $database->find('projectTasks', [
            Query::equal('parentTaskId', [$taskId]),
            Query::orderAsc('sortOrder'),
            Query::limit(100),
        ]);

        $groups = $database->find('stickerGroups', [Query::equal('taskId', [$taskId]), Query::limit(100)]);
        $currentSummaryIds = \array_values(
            \array_filter(
                \array_map(
                    static fn (Document $group): string => (string) $group->getAttribute('currentSummaryId', ''),
                    $groups,
                ),
            ),
        );
        $stickers = $database->find('stickers', [
            Query::equal('taskId', [$taskId]),
            Query::equal('status', ['valid']),
            Query::limit(500),
        ]);
        $relevantSummaries = empty($currentSummaryIds)
            ? []
            : $database->find('stickerGroupSummaries', [Query::equal('$id', $currentSummaryIds), Query::limit(100)]);
        $runs = $database->find('agentRuns', [Query::equal('taskId', [$taskId]), Query::limit(25)]);
        $evidence = $database->find('evidence', [Query::equal('taskId', [$taskId]), Query::limit(100)]);

        $validConstraints = \array_values(
            \array_filter(
                $stickers,
                static fn (Document $sticker): bool => $sticker->getAttribute('type') === 'constraint',
            ),
        );
        $validDecisions = \array_values(
            \array_filter(
                $stickers,
                static fn (Document $sticker): bool => $sticker->getAttribute('type') === 'decision',
            ),
        );

        return new Document([
            'schemaVersion' => Capabilities::SCHEMA_VERSION,
            'contextType' => 'task_execution',
            'task' => $task,
            'parentTasks' => $ancestors,
            'childTasks' => $children,
            'acceptanceCriteria' => $task->getAttribute('acceptanceCriteria', []),
            'directStickers' => $stickers,
            'relevantGroupSummaries' => $relevantSummaries,
            'validConstraints' => $validConstraints,
            'validDecisions' => $validDecisions,
            'previousRuns' => $runs,
            'nextStatusActions' => TaskState::next((string) $task->getAttribute('status', 'draft')),
            'evidence' => $evidence,
            'truncation' => [
                'childTasks' => \count($children) >= 100,
                'directStickers' => \count($stickers) >= 500,
                'previousRuns' => \count($runs) >= 25,
                'evidence' => \count($evidence) >= 100,
            ],
        ]);
    }

    public static function forProject(Database $database, Document $project): Document
    {
        $activeTasks = $database->find('projectTasks', [
            Query::equal('status', ['ready', 'in_progress', 'blocked', 'review']),
            Query::limit(500),
        ]);
        $recentTasks = $database->find('projectTasks', [Query::orderDesc('$updatedAt'), Query::limit(100)]);
        $projectStickers = $database->find('stickers', [Query::equal('status', ['valid']), Query::limit(500)]);
        $groups = $database->find('stickerGroups', [Query::orderDesc('$updatedAt'), Query::limit(100)]);
        $summaryIds = \array_values(
            \array_filter(
                \array_map(
                    static fn (Document $group): string => (string) $group->getAttribute('currentSummaryId', ''),
                    $groups,
                ),
            ),
        );
        $summaries = empty($summaryIds)
            ? []
            : $database->find('stickerGroupSummaries', [Query::equal('$id', $summaryIds), Query::limit(100)]);

        return new Document([
            'schemaVersion' => Capabilities::SCHEMA_VERSION,
            'contextType' => 'project_management',
            'project' => $project,
            'activeTasks' => $activeTasks,
            'recentTasks' => $recentTasks,
            'projectStickers' => $projectStickers,
            'stickerGroupSummaries' => $summaries,
            'recentActivity' => $database->find('activityEvents', [Query::orderDesc('$createdAt'), Query::limit(100)]),
            'repositories' => [],
            'workspaces' => [],
            'truncation' => [
                'activeTasks' => \count($activeTasks) >= 500,
                'recentTasks' => \count($recentTasks) >= 100,
                'projectStickers' => \count($projectStickers) >= 500,
                'stickerGroupSummaries' => \count($summaries) >= 100,
            ],
        ]);
    }
}
