<?php

declare(strict_types=1);

namespace Tests\Unit\Devices;

use Appwrite\Devices\Mqtt;
use PHPUnit\Framework\TestCase;

final class MqttTest extends TestCase
{
    public function testMessagePublishKeepsEmqxConvention(): void
    {
        $this->assertSame([
            'category' => 'message',
            'action' => 'publish',
        ], Mqtt::getAppwriteEventParts('message.publish'));
    }

    public function testOtherEmqxEventsKeepTheirNames(): void
    {
        $this->assertSame([
            'category' => 'client',
            'action' => 'connected',
        ], Mqtt::getAppwriteEventParts('client.connected'));
    }

    public function testUnknownEventIsRejected(): void
    {
        $this->assertNull(Mqtt::getAppwriteEventParts('message.unknown'));
    }
}
