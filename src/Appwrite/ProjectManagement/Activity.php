<?php

namespace Appwrite\ProjectManagement;

use Appwrite\Utopia\Database\Documents\User;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Validator\Authorization;

class Activity
{
    public static function record(
        Database $database,
        Authorization $authorization,
        User $user,
        string $entityType,
        string $entityId,
        string $eventType,
        array $payload,
        array $permissions,
    ): Document {
        $roles = $authorization->getRoles();
        $actorType = $user->isKey($roles) ? 'agent' : 'user';

        return $authorization->skip(
            fn () => $database->createDocument(
                'activityEvents',
                new Document([
                    '$id' => ID::unique(),
                    '$collection' => 'activityEvents',
                    '$permissions' => $permissions,
                    'entityType' => $entityType,
                    'entityId' => $entityId,
                    'eventType' => $eventType,
                    'actorType' => $actorType,
                    'actorId' => $user->getId(),
                    'payload' => $payload,
                ]),
            ),
        );
    }
}
