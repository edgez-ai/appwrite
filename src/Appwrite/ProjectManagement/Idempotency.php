<?php

namespace Appwrite\ProjectManagement;

use Appwrite\Extend\Exception;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Validator\Authorization;

class Idempotency
{
    public static function hash(array $input): string
    {
        $normalize = function (mixed $value) use (&$normalize): mixed {
            if (!\is_array($value)) {
                return $value;
            }

            if (!\array_is_list($value)) {
                \ksort($value, SORT_STRING);
            }
            foreach ($value as $key => $item) {
                $value[$key] = $normalize($item);
            }

            return $value;
        };

        return \hash('sha256', \json_encode($normalize($input), JSON_THROW_ON_ERROR));
    }

    public static function resolveCreateRetry(
        Database $database,
        Authorization $authorization,
        string $collection,
        string $id,
        array $expected,
    ): Document {
        $existing = $authorization->skip(fn () => $database->getDocument($collection, $id));
        if ($existing->isEmpty() || !self::matches($existing, $expected)) {
            throw new Exception(
                Exception::PROJECT_RESOURCE_ALREADY_EXISTS,
                "Resource '{$id}' already exists with different input.",
            );
        }

        return $existing;
    }

    public static function matches(Document $document, array $expected): bool
    {
        foreach ($expected as $key => $value) {
            if ($document->getAttribute($key) !== $value) {
                return false;
            }
        }

        return true;
    }
}
