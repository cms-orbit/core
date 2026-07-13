<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Presenters;

use CmsOrbit\Core\Presenter\Presenter;
use CmsOrbit\Core\Screen\Contracts\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/**
 * Default presenter for the Orbit base user model.
 *
 * Lives inside the package so a plain host application does not need to publish
 * or author any presenter class for the admin panel (and global search) to work.
 */
class UserPresenter extends Presenter implements Searchable
{
    public function perSearchShow(): int
    {
        return 5;
    }

    public function searchQuery(?string $query = null): Builder
    {
        /** @var Model $model */
        $model = $this->entity;

        $builder = $model->newQuery();

        if ($query !== null && $query !== '') {
            $builder->where(function (Builder $inner) use ($query): void {
                $inner->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            });
        }

        return $builder;
    }

    public function label(): string
    {
        return __('Users');
    }

    public function title(): string
    {
        return (string) $this->entity->getAttribute('name');
    }

    public function subTitle(): string
    {
        return (string) $this->entity->getAttribute('email');
    }

    public function url(): string
    {
        if (! Route::has('orbit.entities.users.edit')) {
            return '';
        }

        return route('orbit.entities.users.edit', ['id' => $this->entity->getKey()]);
    }

    public function image(): ?string
    {
        return null;
    }
}
