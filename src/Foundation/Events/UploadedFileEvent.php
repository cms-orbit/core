<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Events;

use CmsOrbit\Core\Attachment\Models\Attachment;
use Illuminate\Queue\SerializesModels;

/**
 * This class represents the event that fires after a file is uploaded.
 */
class UploadedFileEvent
{
    use SerializesModels;

    /**
     * UploadedFileEvent constructor.
     */
    public function __construct(public Attachment $attachment) {}
}
