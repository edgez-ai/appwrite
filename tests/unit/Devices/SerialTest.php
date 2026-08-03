<?php

declare(strict_types=1);

namespace Tests\Unit\Devices;

use Appwrite\Devices\Validator\Serial;
use PHPUnit\Framework\TestCase;

final class SerialTest extends TestCase
{
    public function testAcceptsMqttSafeCustomerSerials(): void
    {
        $validator = new Serial();

        $this->assertTrue($validator->isValid('SN-2026_00042.rev:1'));
        $this->assertFalse($validator->isValid(''));
        $this->assertFalse($validator->isValid('/factory/device'));
        $this->assertFalse($validator->isValid('device+#'));
        $this->assertFalse($validator->isValid('device serial'));
    }
}
