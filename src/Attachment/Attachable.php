<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Attachment;

use CmsOrbit\Core\Attachment\Models\Attachment;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * This trait is used to relate or attach multiple files with Eloquent models.
 */
trait Attachable
{
    /**
     * @deprecated Use the `attachment` method instead.
     * This method will be removed in the next major release.
     *
     * Get all the attachments associated with the given model.
     */
    public function attachment(?string $group = null): MorphToMany
    {
        return $this->attachments($group);
    }

    /**
     * Get all the attachments associated with the given model.
     */
    public function attachments(?string $group = null): MorphToMany
    {
        return $this->morphToMany(
            Orbit::model(Attachment::class),
            'attachmentable',
            'attachmentable',
            'attachmentable_id',
            'attachment_id'
        )
            ->when($group !== null, fn ($query) => $query->where('group', $group))
            ->orderBy('sort');
    }
}
