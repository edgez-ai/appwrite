<?php

declare(strict_types=1);

namespace Tests\Unit\ProjectManagement;

use Appwrite\ProjectManagement\AgentRunState;
use PHPUnit\Framework\TestCase;

class AgentRunStateTest extends TestCase
{
    public function testExecutionLifecycle(): void
    {
        $this->assertTrue(AgentRunState::canTransition('queued', 'running'));
        $this->assertTrue(AgentRunState::canTransition('running', 'succeeded'));
        $this->assertTrue(AgentRunState::canTransition('failed', 'queued'));
        $this->assertFalse(AgentRunState::canTransition('queued', 'succeeded'));
        $this->assertFalse(AgentRunState::canTransition('succeeded', 'running'));
    }
}
