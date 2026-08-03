<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;
use Utopia\Database\Document;

class Device extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Device ID.',
                'default' => '',
                'example' => '550e8400-e29b-41d4-a716-446655440000',
            ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Device creation time in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('$updatedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Device update time in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('$permissions', [
                'type' => self::TYPE_STRING,
                'description' => 'Device permissions. [Learn more about permissions](https://appwrite.io/docs/permissions).',
                'default' => [],
                'example' => ['read("user:5e5bb8c16897e")'],
                'array' => true,
            ])
            ->addRule('name', [
                'type' => self::TYPE_STRING,
                'description' => 'Device name.',
                'default' => '',
                'example' => 'Office thermostat',
            ])
            ->addRule('serial', [
                'type' => self::TYPE_STRING,
                'description' => 'Customer-defined serial number, unique within the project.',
                'default' => '',
                'example' => 'SN-2026-00042',
            ])
            ->addRule('enabled', [
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Whether the device can authenticate with the MQTT broker.',
                'default' => true,
                'example' => true,
            ])
            ->addRule('status', [
                'type' => self::TYPE_STRING,
                'description' => 'Last reported device connection status.',
                'default' => 'offline',
                'example' => 'online',
            ])
            ->addRule('lastSeenAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Last time the device authenticated or reported activity.',
                'required' => false,
                'default' => null,
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('metadata', [
                'type' => self::TYPE_JSON,
                'description' => 'Developer-defined device metadata.',
                'default' => [],
                'example' => ['model' => 'T1000', 'location' => 'office'],
            ]);
    }

    public function filter(Document $document): Document
    {
        $document->removeAttribute('search');

        return $document;
    }

    public function getName(): string
    {
        return 'Device';
    }

    public function getType(): string
    {
        return Response::MODEL_DEVICE;
    }
}
