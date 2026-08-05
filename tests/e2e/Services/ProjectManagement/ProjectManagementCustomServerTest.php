<?php

declare(strict_types=1);

namespace Tests\E2E\Services\ProjectManagement;

use Tests\E2E\Client;
use Tests\E2E\Scopes\ProjectCustom;
use Tests\E2E\Scopes\Scope;
use Tests\E2E\Scopes\SideServer;
use Utopia\Database\Helpers\ID;

final class ProjectManagementCustomServerTest extends Scope
{
    use ProjectCustom;
    use SideServer;

    private function getProjectManagementHeaders(
        array $scopes = ['projectManagement.read', 'projectManagement.write'],
    ): array {
        return [
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'x-appwrite-key' => $this->getNewKey($scopes),
        ];
    }

    public function testAgentCanDiscoverAndReplayCreateSafely(): void
    {
        $headers = $this->getProjectManagementHeaders();
        $capabilities = $this->client->call(Client::METHOD_GET, '/project-management/capabilities', $headers);

        $this->assertSame(200, $capabilities['headers']['status-code']);
        $this->assertSame('projectManagement', $capabilities['body']['service']);
        $this->assertSame('idempotent_for_same_agent', $capabilities['body']['agentActions']['claimTask']['retry']);

        $taskId = ID::unique();
        $input = ['taskId' => $taskId, 'title' => 'Retry-safe task', 'status' => 'ready'];
        $created = $this->client->call(Client::METHOD_POST, '/tasks', $headers, $input);
        $replayed = $this->client->call(Client::METHOD_POST, '/tasks', $headers, $input);

        $this->assertSame(201, $created['headers']['status-code']);
        $this->assertSame(200, $replayed['headers']['status-code']);
        $this->assertSame($created['body']['$id'], $replayed['body']['$id']);

        $context = $this->client->call(Client::METHOD_GET, "/tasks/{$taskId}/context", $headers);
        $this->assertSame(200, $context['headers']['status-code']);
        $this->assertSame('task_execution', $context['body']['contextType']);
        $this->assertContains('in_progress', $context['body']['nextStatusActions']);
    }

    public function testClaimAndStatusTransitionUseOptimisticConcurrency(): void
    {
        $headers = $this->getProjectManagementHeaders();
        $task = $this->client->call(Client::METHOD_POST, '/tasks', $headers, [
            'taskId' => ID::unique(),
            'title' => 'Claimable task',
            'status' => 'ready',
        ]);
        $taskId = $task['body']['$id'];

        $claim = $this->client->call(Client::METHOD_PATCH, "/tasks/{$taskId}/claim", $headers, [
            'agentId' => 'development-agent-1',
        ]);
        $replayedClaim = $this->client->call(Client::METHOD_PATCH, "/tasks/{$taskId}/claim", $headers, [
            'agentId' => 'development-agent-1',
        ]);
        $competingClaim = $this->client->call(Client::METHOD_PATCH, "/tasks/{$taskId}/claim", $headers, [
            'agentId' => 'development-agent-2',
        ]);

        $this->assertSame(200, $claim['headers']['status-code']);
        $this->assertSame('in_progress', $claim['body']['status']);
        $this->assertSame(200, $replayedClaim['headers']['status-code']);
        $this->assertSame(409, $competingClaim['headers']['status-code']);
        $this->assertSame('project_task_status_conflict', $competingClaim['body']['type']);

        $review = $this->client->call(Client::METHOD_PATCH, "/tasks/{$taskId}/status", $headers, [
            'expectedStatus' => 'in_progress',
            'status' => 'review',
            'reason' => 'Implementation and tests completed.',
        ]);
        $stale = $this->client->call(Client::METHOD_PATCH, "/tasks/{$taskId}/status", $headers, [
            'expectedStatus' => 'in_progress',
            'status' => 'blocked',
        ]);

        $this->assertSame(200, $review['headers']['status-code']);
        $this->assertSame('review', $review['body']['status']);
        $this->assertSame(409, $stale['headers']['status-code']);
        $this->assertSame('project_task_status_conflict', $stale['body']['type']);
    }
}
