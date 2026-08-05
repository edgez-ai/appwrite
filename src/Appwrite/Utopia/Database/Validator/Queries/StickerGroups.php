<?php

namespace Appwrite\Utopia\Database\Validator\Queries;

class StickerGroups extends Base
{
    public const ALLOWED_ATTRIBUTES = ['taskId', 'title', 'summaryStatus', 'lastStickerAt'];

    public function __construct()
    {
        parent::__construct('stickerGroups', self::ALLOWED_ATTRIBUTES);
    }
}
