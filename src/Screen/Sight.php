<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\View;

class Sight extends Cell
{
    /**
     * Serialize the sight to the React JSON contract (ColumnNode).
     *
     * Render (closure) columns are pre-rendered to an HTML string; plain
     * columns expose their data key so the value is read from the row client
     * side.
     *
     * @param  Repository|Model  $repository
     * @return array<string, mixed>
     */
    public function toArray($repository = null): array
    {
        $rendered = null;

        if ($this->render !== null && $repository !== null) {
            $value = $this->handler($repository);
            $rendered = $value instanceof Htmlable ? $value->toHtml() : (string) $value;
        }

        return [
            'name' => $this->name,
            'column' => $this->column,
            'title' => $this->title,
            'slug' => Str::slug($this->name),
            'popover' => $this->popover,
            'rendered' => $rendered,
        ];
    }

    /**
     * Builds a column heading.
     *
     * @return Factory|View
     */
    public function buildDt()
    {
        return view('orbit::partials.layouts.dt', [
            'column' => $this->column,
            'title' => $this->title,
            'popover' => $this->popover,
        ]);
    }

    /**
     * Builds content for the column.
     *
     * @param  Repository|Model  $repository
     * @return string|Htmlable|null
     */
    public function buildDd($repository)
    {
        $value = $this->render
            ? $this->handler($repository)
            : $repository->getContent($this->name);

        return $this->render === null
            ? e($value)
            : $value;
    }
}
