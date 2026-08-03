<?php

namespace Appwrite\Devices;

use Utopia\Auth\Hashes\Argon2;

class Credentials
{
    public const int SECRET_BYTES = 32;

    public function generateSecret(): string
    {
        return \rtrim(\strtr(\base64_encode(\random_bytes(self::SECRET_BYTES)), '+/', '-_'), '=');
    }

    public function hash(string $secret): string
    {
        return (new Argon2())->hash($secret);
    }

    public function verify(string $secret, string $hash): bool
    {
        return (new Argon2())->verify($secret, $hash);
    }

    public function getUsername(string $serial): string
    {
        return $serial;
    }

    public function getClientId(string $deviceId): string
    {
        return $deviceId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAcl(string $projectId, string $serial): array
    {
        $prefix = "projects/{$projectId}/devices/{$serial}";

        return [
            [
                'permission' => 'allow',
                'action' => 'publish',
                'topic' => "{$prefix}/telemetry/#",
                'qos' => [0, 1, 2],
                'retain' => false,
            ],
            [
                'permission' => 'allow',
                'action' => 'publish',
                'topic' => "{$prefix}/events/#",
                'qos' => [0, 1, 2],
            ],
            [
                'permission' => 'allow',
                'action' => 'subscribe',
                'topic' => "{$prefix}/commands/#",
                'qos' => [0, 1, 2],
            ],
            [
                'permission' => 'deny',
                'action' => 'all',
                'topic' => '#',
            ],
        ];
    }
}
