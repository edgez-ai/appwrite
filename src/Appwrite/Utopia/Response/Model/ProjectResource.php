<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response\Model;
use Utopia\Database\Document;

abstract class ProjectResource extends Model
{
    protected function addResourceRules(string $resource): static
    {
        $this->addRule('$id', [
            'type' => self::TYPE_STRING,
            'description' => "{$resource} ID.",
            'default' => '',
            'example' => 'unique()',
        ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => "{$resource} creation time in ISO 8601 format.",
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('$updatedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => "{$resource} update time in ISO 8601 format.",
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('$permissions', [
                'type' => self::TYPE_STRING,
                'description' => "{$resource} permissions.",
                'default' => [],
                'example' => ['read("user:5e5bb8c16897e")'],
                'array' => true,
            ]);

        return $this;
    }

    public function filter(Document $document): Document
    {
        $document->removeAttribute('search');
        $document->removeAttribute('bindingKey');
        $document->removeAttribute('createHash');

        return $document;
    }
}
