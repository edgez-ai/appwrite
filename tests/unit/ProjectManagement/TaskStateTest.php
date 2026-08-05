<?php

declare(strict_types=1);

namespace Tests\Unit\ProjectManagement;

use Appwrite\ProjectManagement\TaskState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskStateTest extends TestCase
{
    #[DataProvider('transitionProvider')]
    public function testTransitionRules(string $from, string $to, bool $allowed): void
    {
        $this->assertSame($allowed, TaskState::canTransition($from, $to));
    }

    public static function transitionProvider(): array
    {
        return [
            'ready can be claimed' => ['ready', 'in_progress', true],
            'development can submit review' => ['in_progress', 'review', true],
            'review can complete' => ['review', 'done', true],
            'draft cannot skip to done' => ['draft', 'done', false],
            'done cannot become ready directly' => ['done', 'ready', false],
            'retrying the same state is idempotent' => ['review', 'review', true],
        ];
    }

    public function testNextActionsAreStable(): void
    {
        $this->assertSame(['blocked', 'review', 'cancelled'], TaskState::next('in_progress'));
        $this->assertSame([], TaskState::next('unknown'));
    }
}
