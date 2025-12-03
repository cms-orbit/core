<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Events;

use Illuminate\Queue\SerializesModels;
use CmsOrbit\Core\Foundation\Attachments\Models\Attachment;

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
