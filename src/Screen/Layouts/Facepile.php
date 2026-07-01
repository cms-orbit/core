<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use ArrayAccess;
use CmsOrbit\Core\Screen\Contracts\Personable;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\View\View;

class Facepile extends Content
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.facepile';

    /**
     * @param  Personable[]  $users
     */
    public function render(ArrayAccess $users): View
    {
        return view($this->template, [
            'users' => $users,
        ]);
    }

    /**
     * Serialize the avatar images to the React contract.
     *
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        $users = $this->resolveTarget($repository);

        if (! is_iterable($users)) {
            return ['images' => []];
        }

        $images = collect($users)
            ->map(fn ($user) => $user instanceof Personable ? $user->image() : null)
            ->filter()
            ->values()
            ->all();

        return ['images' => $images];
    }
}
