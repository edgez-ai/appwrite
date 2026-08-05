<?php

declare(strict_types=1);

namespace Tests\Unit\ProjectManagement;

use Appwrite\ProjectManagement\Idempotency;
use PHPUnit\Framework\TestCase;
use Utopia\Database\Document;

class IdempotencyTest extends TestCase
{
    public function testMatchingCreateInputIsSafeToReplay(): void
    {
        $document = new Document(['title' => 'Task', 'status' => 'draft', 'metadata' => ['source' => 'slack']]);

        $this->assertTrue(
            Idempotency::matches($document, [
                'title' => 'Task',
                'status' => 'draft',
                'metadata' => ['source' => 'slack'],
            ]),
        );
    }

    public function testChangedCreateInputIsAConflict(): void
    {
        $document = new Document(['title' => 'Task', 'status' => 'draft']);

        $this->assertFalse(Idempotency::matches($document, ['title' => 'Different task']));
    }

    public function testHashIsStableAcrossObjectKeyOrder(): void
    {
        $first = Idempotency::hash(['title' => 'Task', 'metadata' => ['channel' => 'C1', 'provider' => 'slack']]);
        $second = Idempotency::hash(['metadata' => ['provider' => 'slack', 'channel' => 'C1'], 'title' => 'Task']);

        $this->assertSame($first, $second);
    }
}
