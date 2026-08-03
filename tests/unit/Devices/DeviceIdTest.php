<?php

declare(strict_types=1);

namespace Tests\Unit\Devices;

use Appwrite\Devices\DeviceId;
use PHPUnit\Framework\TestCase;

final class DeviceIdTest extends TestCase
{
    public function testGeneratesUniqueVersionFourUuid(): void
    {
        $first = DeviceId::generate();
        $second = DeviceId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $first,
        );
        $this->assertNotSame($first, $second);
    }
}
