<?php

declare(strict_types=1);

namespace Tests\Unit\Devices;

use Appwrite\Devices\Credentials;
use PHPUnit\Framework\TestCase;

final class CredentialsTest extends TestCase
{
    public function testGeneratedSecretCanBeVerified(): void
    {
        $credentials = new Credentials();
        $secret = $credentials->generateSecret();
        $hash = $credentials->hash($secret);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $secret);
        $this->assertTrue($credentials->verify($secret, $hash));
        $this->assertFalse($credentials->verify($secret . 'invalid', $hash));
    }

    public function testIdentityUsesSerialAndGeneratedDeviceId(): void
    {
        $credentials = new Credentials();

        $this->assertSame('SN-2026-00042', $credentials->getUsername('SN-2026-00042'));
        $this->assertSame('device-uuid', $credentials->getClientId('device-uuid'));
    }

    public function testAclOnlyAllowsTheResolvedDeviceTopics(): void
    {
        $acl = (new Credentials())->getAcl('project-a', 'SN-2026-00042');

        $this->assertSame('projects/project-a/devices/SN-2026-00042/telemetry/#', $acl[0]['topic']);
        $this->assertSame('publish', $acl[0]['action']);
        $this->assertFalse($acl[0]['retain']);
        $this->assertSame('projects/project-a/devices/SN-2026-00042/events/#', $acl[1]['topic']);
        $this->assertSame('projects/project-a/devices/SN-2026-00042/commands/#', $acl[2]['topic']);
        $this->assertSame('subscribe', $acl[2]['action']);
        $this->assertSame('deny', $acl[3]['permission']);
        $this->assertSame('#', $acl[3]['topic']);
    }
}
