<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Events;

use CmsOrbit\Core\Attachment\Models\Attachment;
use Illuminate\Queue\SerializesModels;

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
