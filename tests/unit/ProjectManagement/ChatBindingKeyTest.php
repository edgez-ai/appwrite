<?php

declare(strict_types=1);

namespace Tests\Unit\ProjectManagement;

use Appwrite\ProjectManagement\ChatBindingKey;
use PHPUnit\Framework\TestCase;

class ChatBindingKeyTest extends TestCase
{
    public function testSameConversationProducesSameOpaqueKey(): void
    {
        $first = ChatBindingKey::generate('slack', 'tenant', 'channel', 'thread');
        $second = ChatBindingKey::generate('slack', 'tenant', 'channel', 'thread');

        $this->assertSame($first, $second);
        $this->assertSame(64, \strlen($first));
        $this->assertDoesNotMatchRegularExpression('/tenant|channel|thread/', $first);
    }

    public function testProviderAndThreadArePartOfIdentity(): void
    {
        $slack = ChatBindingKey::generate('slack', 'tenant', 'channel', 'thread');

        $this->assertNotSame($slack, ChatBindingKey::generate('teams', 'tenant', 'channel', 'thread'));
        $this->assertNotSame($slack, ChatBindingKey::generate('slack', 'tenant', 'channel', 'other-thread'));
    }
}
