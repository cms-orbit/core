<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Events;

use Illuminate\Queue\SerializesModels;
use CmsOrbit\Core\Attachment\Models\Attachment;

/**
 * Class ReplicateFileEvent.
 */
class ReplicateFileEvent
{
    use SerializesModels;

    /**
     * ReplicateFileEvent constructor.
     */
    public function __construct(public Attachment $attachment, public int $time) {}
}
