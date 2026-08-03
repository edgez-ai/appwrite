<?php

declare(strict_types=1);

namespace Tests\E2E\Services\Devices;

use Tests\E2E\Client;
use Tests\E2E\Scopes\ProjectCustom;
use Tests\E2E\Scopes\Scope;
use Tests\E2E\Scopes\SideClient;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Helpers\Permission;
use Utopia\Database\Helpers\Role;

final class DevicesCustomClientTest extends Scope
{
    use ProjectCustom;
    use SideClient;

    public function testUserOwnsDeviceByDefault(): void
    {
        $headers = \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders(false));

        $create = $this->client->call(Client::METHOD_POST, '/devices', $headers, [
            'serial' => 'user-' . ID::unique(),
            'name' => 'User device',
        ]);

        $userRole = Role::user($this->getUser()['$id']);
        $this->assertSame(201, $create['headers']['status-code']);
        $this->assertContains(Permission::read($userRole), $create['body']['$permissions']);
        $this->assertContains(Permission::update($userRole), $create['body']['$permissions']);
        $this->assertContains(Permission::delete($userRole), $create['body']['$permissions']);

        $deviceId = $create['body']['$id'];
        $get = $this->client->call(Client::METHOD_GET, '/devices/' . $deviceId, $headers);
        $this->assertSame(200, $get['headers']['status-code']);
    }

    public function testTeamPermissionsGrantDeviceAccess(): void
    {
        $headers = \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders(false));

        $team = $this->client->call(Client::METHOD_POST, '/teams', $headers, [
            'teamId' => ID::unique(),
            'name' => 'Device operators',
        ]);
        $this->assertSame(201, $team['headers']['status-code']);

        $teamRole = Role::team($team['body']['$id']);
        $device = $this->client->call(Client::METHOD_POST, '/devices', $headers, [
            'serial' => 'team-' . ID::unique(),
            'name' => 'Team device',
            'permissions' => [
                Permission::read($teamRole),
                Permission::update($teamRole),
            ],
        ]);

        $this->assertSame(201, $device['headers']['status-code']);
        $this->assertContains(Permission::read($teamRole), $device['body']['$permissions']);
        $this->assertContains(Permission::update($teamRole), $device['body']['$permissions']);

        $credential = $this->client->call(
            Client::METHOD_POST,
            '/devices/' . $device['body']['$id'] . '/credentials',
            $headers,
        );
        $this->assertSame(201, $credential['headers']['status-code']);
        $this->assertNotEmpty($credential['body']['password']);
    }
}
