<?php

declare(strict_types=1);

namespace Tests\Unit\ProjectManagement;

use Appwrite\ProjectManagement\Capabilities;
use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase
{
    public function testContractAdvertisesSafeAgentOperations(): void
    {
        $contract = Capabilities::get();

        $this->assertSame('projectManagement', $contract['service']);
        $this->assertSame(['in_progress', 'draft', 'cancelled'], $contract['taskTransitions']['ready']);
        $this->assertSame('idempotent_for_same_agent', $contract['agentActions']['claimTask']['retry']);
        $this->assertSame('/v1/tasks/{taskId}/status', $contract['agentActions']['transitionTask']['path']);
    }
}
