<?php

namespace Appwrite\Devices;

class Mqtt
{
    public const array EVENTS = [
        'message.publish',
        'message.delivered',
        'message.acked',
        'message.dropped',
        'delivery.dropped',
        'client.connected',
        'client.disconnected',
        'client.connack',
        'client.check_authz_complete',
        'session.subscribed',
        'session.unsubscribed',
    ];

    /**
     * @return array{category: string, action: string}|null
     */
    public static function getAppwriteEventParts(string $event): ?array
    {
        if (!\in_array($event, self::EVENTS, true)) {
            return null;
        }

        [$category, $action] = \explode('.', $event, 2);

        return [
            'category' => $category,
            'action' => $action,
        ];
    }
}
