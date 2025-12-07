<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Settings\Events;

use Illuminate\Queue\SerializesModels;
use CmsOrbit\Core\Foundation\Attachments\Models\Attachment;

/**
 * Class UploadFileEvent.
 */
class UploadFileEvent
{
    use SerializesModels;

    /**
     * UploadFileEvent constructor.
     */
    public function __construct(public Attachment $attachment, public int $time)
    {
        // ..
    }
}
