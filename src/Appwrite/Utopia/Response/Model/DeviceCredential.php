<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;

class DeviceCredential extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Credential ID.',
                'default' => '',
                'example' => 'credential-id',
            ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Credential creation time in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('$updatedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Credential update time in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('deviceId', [
                'type' => self::TYPE_STRING,
                'description' => 'Device ID.',
                'default' => '',
                'example' => '550e8400-e29b-41d4-a716-446655440000',
            ])
            ->addRule('username', [
                'type' => self::TYPE_STRING,
                'description' => 'MQTT username. This is the customer-defined device serial number.',
                'default' => '',
                'example' => 'SN-2026-00042',
            ])
            ->addRule('clientId', [
                'type' => self::TYPE_STRING,
                'description' => 'MQTT client identifier. This is the generated device ID.',
                'default' => '',
                'example' => '550e8400-e29b-41d4-a716-446655440000',
            ])
            ->addRule('password', [
                'type' => self::TYPE_STRING,
                'format' => 'password',
                'description' => 'MQTT password. Returned only when credentials are created or rotated.',
                'default' => '',
                'example' => 'generated-secret',
            ])
            ->addRule('expire', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Credential expiry time.',
                'required' => false,
                'default' => null,
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ]);
    }

    public function getName(): string
    {
        return 'Device credential';
    }

    public function getType(): string
    {
        return Response::MODEL_DEVICE_CREDENTIAL;
    }
}
