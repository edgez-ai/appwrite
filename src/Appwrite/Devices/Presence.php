<?php

namespace Appwrite\Devices;

use Utopia\Database\DateTime as DatabaseDateTime;
use Utopia\Database\Document;

class Presence
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function getChanges(string $event, array $payload, Document $device): array
    {
        $changes = [];
        $eventAt = self::getTimestamp($payload, ['timestamp', 'disconnected_at', 'connected_at']);
        $lastSeenAt = $device->getAttribute('lastSeenAt');

        if ($eventAt !== null && (!\is_string($lastSeenAt) || $eventAt > self::toTimestamp($lastSeenAt))) {
            $changes['lastSeenAt'] = self::fromTimestamp($eventAt);
        }

        $connectedAt = self::getTimestamp($payload, ['connected_at', 'timestamp']);
        $storedConnectedAt = $device->getAttribute('mqttConnectedAt');
        $storedConnectedAt = \is_int($storedConnectedAt) ? $storedConnectedAt : null;

        if ($event === 'client.connected' && $connectedAt !== null) {
            if ($storedConnectedAt === null || $connectedAt > $storedConnectedAt) {
                $changes['status'] = 'online';
                $changes['mqttConnectedAt'] = $connectedAt;
            }
        }

        if ($event === 'client.disconnected' && $connectedAt !== null) {
            if ($storedConnectedAt === null || $connectedAt >= $storedConnectedAt) {
                $changes['status'] = 'offline';
                $changes['mqttConnectedAt'] = $connectedAt;
            }
        }

        return $changes;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string> $keys
     */
    private static function getTimestamp(array $payload, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if ((\is_int($value) || \is_float($value) || \is_string($value)) && \is_numeric($value)) {
                $timestamp = (int) $value;
                if ($timestamp > 0) {
                    return $timestamp;
                }
            }
        }

        return null;
    }

    private static function fromTimestamp(int $timestamp): string
    {
        $seconds = \intdiv($timestamp, 1000);
        $microseconds = ($timestamp % 1000) * 1000;
        $date = \DateTime::createFromFormat('U.u', \sprintf('%d.%06d', $seconds, $microseconds));

        if ($date === false) {
            return DatabaseDateTime::now();
        }

        $date->setTimezone(new \DateTimeZone(\date_default_timezone_get()));

        return DatabaseDateTime::format($date);
    }

    private static function toTimestamp(string $datetime): int
    {
        try {
            $date = new \DateTime($datetime);
            return ((int) $date->format('U') * 1000) + (int) $date->format('v');
        } catch (\Throwable) {
            return 0;
        }
    }
}
