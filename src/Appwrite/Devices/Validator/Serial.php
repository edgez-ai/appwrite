<?php

namespace Appwrite\Devices\Validator;

use Utopia\Database\Database;
use Utopia\Validator;

class Serial extends Validator
{
    public function isValid($value): bool
    {
        return \is_string($value)
            && \mb_strlen($value) <= Database::LENGTH_KEY
            && \preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]*$/', $value) === 1;
    }

    public function getDescription(): string
    {
        return 'Serial numbers must start with a letter or number and contain only letters, numbers, periods, hyphens, underscores, and colons.';
    }

    public function isArray(): bool
    {
        return false;
    }

    public function getType(): string
    {
        return self::TYPE_STRING;
    }
}
