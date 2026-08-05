<?php

namespace Appwrite\ProjectManagement;

use Appwrite\Extend\Exception;
use Appwrite\Utopia\Database\Documents\User;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Helpers\Permission;
use Utopia\Database\Helpers\Role;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\Authorization\Input;

class Permissions
{
    private const array ALLOWED = [
        Database::PERMISSION_READ,
        Database::PERMISSION_UPDATE,
        Database::PERMISSION_DELETE,
        Database::PERMISSION_WRITE,
    ];

    public function setOnCreate(
        Document $resource,
        ?array $permissions,
        User $user,
        Authorization $authorization,
    ): Document {
        $permissions = Permission::aggregate($permissions, self::ALLOWED);
        $roles = $authorization->getRoles();
        $isPrivileged = $user->isKey($roles) || $user->isPrivileged($roles);

        if ($permissions === null) {
            $permissions = [];
            if (!$isPrivileged && !empty($user->getId())) {
                $role = Role::user($user->getId());
                $permissions = [Permission::read($role), Permission::update($role), Permission::delete($role)];
            }
        }

        if (!$isPrivileged) {
            $this->validateAssignableRoles($permissions, $authorization);
        }

        \sort($permissions, SORT_STRING);
        $resource->setAttribute('$permissions', $permissions);

        return $resource;
    }

    public function setOnUpdate(
        Document $changes,
        ?array $permissions,
        User $user,
        Authorization $authorization,
    ): Document {
        if ($permissions === null) {
            return $changes;
        }

        $permissions = Permission::aggregate($permissions, self::ALLOWED) ?? [];
        $roles = $authorization->getRoles();
        if (!$user->isKey($roles) && !$user->isPrivileged($roles)) {
            $this->validateAssignableRoles($permissions, $authorization);
        }

        \sort($permissions, SORT_STRING);
        $changes->setAttribute('$permissions', $permissions);

        return $changes;
    }

    public function assert(Document $resource, string $permission, User $user, Authorization $authorization): void
    {
        $roles = $authorization->getRoles();
        if ($user->isKey($roles) || $user->isPrivileged($roles)) {
            return;
        }

        $allowed = match ($permission) {
            Database::PERMISSION_READ => $resource->getRead(),
            Database::PERMISSION_UPDATE => $resource->getUpdate(),
            Database::PERMISSION_DELETE => $resource->getDelete(),
            default => [],
        };

        if (!$authorization->isValid(new Input($permission, $allowed))) {
            throw new Exception(Exception::USER_UNAUTHORIZED, $authorization->getDescription());
        }
    }

    private function validateAssignableRoles(array $permissions, Authorization $authorization): void
    {
        foreach ($permissions as $value) {
            $permission = Permission::parse($value);
            $role = (new Role(
                $permission->getRole(),
                $permission->getIdentifier(),
                $permission->getDimension(),
            ))->toString();

            if (!$authorization->hasRole($role)) {
                throw new Exception(
                    Exception::USER_UNAUTHORIZED,
                    'Permissions must be one of: (' . \implode(', ', $authorization->getRoles()) . ')',
                );
            }
        }
    }
}
