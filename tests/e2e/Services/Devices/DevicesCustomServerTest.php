<?php

declare(strict_types=1);

namespace Tests\E2E\Services\Devices;

use Tests\E2E\Client;
use Tests\E2E\Scopes\ProjectCustom;
use Tests\E2E\Scopes\Scope;
use Tests\E2E\Scopes\SideServer;
use Utopia\Database\Helpers\ID;

final class DevicesCustomServerTest extends Scope
{
    use ProjectCustom;
    use SideServer;

    private function getDeviceKey(array $scopes = ['devices.read', 'devices.write']): string
    {
        return $this->getNewKey($scopes);
    }

    public function testProjectKeyManagesAllProjectDevicesAndRotatesCredentials(): void
    {
        $project = $this->getProject();
        $key = $this->getDeviceKey();
        $serial = 'server-' . ID::unique();
        $headers = [
            'content-type' => 'application/json',
            'x-appwrite-project' => $project['$id'],
            'x-appwrite-key' => $key,
        ];

        $create = $this->client->call(Client::METHOD_POST, '/devices', $headers, [
            'serial' => $serial,
            'name' => 'Server managed device',
            'metadata' => ['model' => 'T1000'],
        ]);

        $this->assertSame(201, $create['headers']['status-code']);
        $deviceId = $create['body']['$id'];
        $this->assertNotEmpty($deviceId);
        $this->assertSame($serial, $create['body']['serial']);
        $this->assertSame([], $create['body']['$permissions']);

        $first = $this->client->call(Client::METHOD_POST, "/devices/{$deviceId}/credentials", $headers);
        $second = $this->client->call(Client::METHOD_POST, "/devices/{$deviceId}/credentials", $headers);

        $this->assertSame(201, $first['headers']['status-code']);
        $this->assertSame($serial, $first['body']['username']);
        $this->assertSame($deviceId, $first['body']['clientId']);
        $this->assertNotEmpty($first['body']['password']);
        $this->assertSame($first['body']['$id'], $second['body']['$id']);
        $this->assertNotSame($first['body']['password'], $second['body']['password']);

        $list = $this->client->call(Client::METHOD_GET, '/devices', $headers);
        $this->assertSame(200, $list['headers']['status-code']);
        $this->assertGreaterThanOrEqual(1, $list['body']['total']);
    }

    public function testProjectKeyScopesAreEnforced(): void
    {
        $project = $this->getProject();
        $readKey = $this->getDeviceKey(['devices.read']);

        $create = $this->client->call(Client::METHOD_POST, '/devices', [
            'content-type' => 'application/json',
            'x-appwrite-project' => $project['$id'],
            'x-appwrite-key' => $readKey,
        ], [
            'serial' => 'rejected-' . ID::unique(),
            'name' => 'Rejected device',
        ]);

        $this->assertSame(401, $create['headers']['status-code']);
        $this->assertSame('general_unauthorized_scope', $create['body']['type']);
    }

    public function testSerialIsUniqueWithinProject(): void
    {
        $project = $this->getProject();
        $headers = [
            'content-type' => 'application/json',
            'x-appwrite-project' => $project['$id'],
            'x-appwrite-key' => $this->getDeviceKey(),
        ];
        $serial = 'duplicate-' . ID::unique();

        $first = $this->client->call(Client::METHOD_POST, '/devices', $headers, [
            'serial' => $serial,
            'name' => 'First device',
        ]);
        $second = $this->client->call(Client::METHOD_POST, '/devices', $headers, [
            'serial' => $serial,
            'name' => 'Second device',
        ]);

        $this->assertSame(201, $first['headers']['status-code']);
        $this->assertSame(409, $second['headers']['status-code']);
        $this->assertSame('device_already_exists', $second['body']['type']);
    }
}
