<?php

declare(strict_types=1);

namespace Tests\Unit\Devices;

use Appwrite\Devices\Presence;
use PHPUnit\Framework\TestCase;
use Utopia\Database\Document;

final class PresenceTest extends TestCase
{
    public function testConnectedEventMarksDeviceOnline(): void
    {
        $connectedAt = 1725000000123;
        $changes = Presence::getChanges('client.connected', [
            'connected_at' => $connectedAt,
            'timestamp' => $connectedAt + 10,
        ], new Document([
            'status' => 'offline',
            'lastSeenAt' => null,
            'mqttConnectedAt' => null,
        ]));

        $this->assertSame('online', $changes['status']);
        $this->assertSame($connectedAt, $changes['mqttConnectedAt']);
        $this->assertTimestampSame($connectedAt + 10, $changes['lastSeenAt']);
    }

    public function testCurrentDisconnectMarksDeviceOffline(): void
    {
        $connectedAt = 1725000000123;
        $disconnectedAt = $connectedAt + 5000;
        $changes = Presence::getChanges('client.disconnected', [
            'connected_at' => $connectedAt,
            'disconnected_at' => $disconnectedAt,
        ], new Document([
            'status' => 'online',
            'lastSeenAt' => $this->formatTimestamp($connectedAt),
            'mqttConnectedAt' => $connectedAt,
        ]));

        $this->assertSame('offline', $changes['status']);
        $this->assertSame($connectedAt, $changes['mqttConnectedAt']);
        $this->assertTimestampSame($disconnectedAt, $changes['lastSeenAt']);
    }

    public function testStaleDisconnectDoesNotOverrideNewerConnection(): void
    {
        $newConnectedAt = 1725000010000;
        $changes = Presence::getChanges('client.disconnected', [
            'connected_at' => $newConnectedAt - 5000,
            'disconnected_at' => $newConnectedAt + 100,
        ], new Document([
            'status' => 'online',
            'lastSeenAt' => $this->formatTimestamp($newConnectedAt),
            'mqttConnectedAt' => $newConnectedAt,
        ]));

        $this->assertArrayNotHasKey('status', $changes);
        $this->assertArrayNotHasKey('mqttConnectedAt', $changes);
    }

    public function testDelayedConnectDoesNotOverrideCompletedDisconnect(): void
    {
        $connectedAt = 1725000000123;
        $changes = Presence::getChanges('client.connected', [
            'connected_at' => $connectedAt,
            'timestamp' => $connectedAt,
        ], new Document([
            'status' => 'offline',
            'lastSeenAt' => $this->formatTimestamp($connectedAt + 5000),
            'mqttConnectedAt' => $connectedAt,
        ]));

        $this->assertArrayNotHasKey('status', $changes);
        $this->assertArrayNotHasKey('mqttConnectedAt', $changes);
        $this->assertArrayNotHasKey('lastSeenAt', $changes);
    }

    public function testMessageActivityOnlyUpdatesLastSeen(): void
    {
        $timestamp = 1725000005123;
        $changes = Presence::getChanges('message.publish', [
            'timestamp' => $timestamp,
        ], new Document([
            'status' => 'online',
            'lastSeenAt' => $this->formatTimestamp($timestamp - 5000),
            'mqttConnectedAt' => 1725000000123,
        ]));

        $this->assertSame(['lastSeenAt'], \array_keys($changes));
        $this->assertTimestampSame($timestamp, $changes['lastSeenAt']);
    }

    private function assertTimestampSame(int $expected, string $actual): void
    {
        $date = new \DateTime($actual);
        $actualTimestamp = ((int) $date->format('U') * 1000) + (int) $date->format('v');

        $this->assertSame($expected, $actualTimestamp);
    }

    private function formatTimestamp(int $timestamp): string
    {
        $seconds = \intdiv($timestamp, 1000);
        $microseconds = ($timestamp % 1000) * 1000;
        $date = \DateTime::createFromFormat('U.u', \sprintf('%d.%06d', $seconds, $microseconds));
        if ($date === false) {
            $this->fail('Failed to create test datetime.');
        }
        $date->setTimezone(new \DateTimeZone(\date_default_timezone_get()));

        return $date->format('Y-m-d H:i:s.v');
    }
}
