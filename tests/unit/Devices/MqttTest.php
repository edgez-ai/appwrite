<?php

namespace Tests\Unit\Devices;

use Appwrite\Devices\Mqtt;
use PHPUnit\Framework\TestCase;

final class MqttTest extends TestCase
{
    public function testMessagePublishKeepsEmqxConvention(): void
    {
        self::assertSame([
            'category' => 'message',
            'action' => 'publish',
        ], Mqtt::getAppwriteEventParts('message.publish'));
    }

    public function testOtherEmqxEventsKeepTheirNames(): void
    {
        self::assertSame([
            'category' => 'client',
            'action' => 'connected',
        ], Mqtt::getAppwriteEventParts('client.connected'));
    }

    public function testUnknownEventIsRejected(): void
    {
        self::assertNull(Mqtt::getAppwriteEventParts('message.unknown'));
    }
}
