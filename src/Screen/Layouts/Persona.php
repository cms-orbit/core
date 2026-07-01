<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Contracts\Personable;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\View\View;

class Persona extends Content
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.persona';

    public function render(Personable $user): View
    {
        return view($this->template, [
            'title' => $user->title(),
            'subTitle' => $user->subTitle(),
            'image' => $user->image(),
            'url' => $user->url(),
        ]);
    }

    /**
     * Serialize the persona card to the React contract.
     *
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        $user = $this->resolveTarget($repository);

        if (! $user instanceof Personable) {
            return [];
        }

        return [
            'title' => $user->title(),
            'subTitle' => $user->subTitle(),
            'image' => $user->image(),
            'url' => $user->url(),
        ];
    }
}
