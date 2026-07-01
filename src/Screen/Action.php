<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen;

use CmsOrbit\Core\Screen\Contracts\Actionable;
use CmsOrbit\Core\Support\Color;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class Action extends Field implements Actionable
{
    /**
     * Override the form view.
     *
     * @var string
     */
    protected $typeForm = 'orbit::partials.fields.clear';

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'type',
        'autofocus',
        'disabled',
        'tabindex',
    ];

    /**
     * A set of attributes for the assignment
     * of which will automatically translate them.
     *
     * @var array
     */
    protected $translations = [
        'name',
    ];

    public function name(?string $name = null): self
    {
        return $this->set('name', $name ?? '');
    }

    /**
     * Serialize the action to a FieldNode. Nested action lists (DropDown/Menu
     * `list`) are recursively serialized to FieldNode[] so the React renderer
     * receives action nodes rather than opaque objects. The `source`
     * (Repository) attribute is stripped — it is never JSON-serializable.
     *
     * @return array<string, mixed>|null
     */
    public function toArray(): ?array
    {
        $node = parent::toArray();

        if ($node === null) {
            return null;
        }

        if (Arr::has($node, 'attributes.source')) {
            unset($node['attributes']['source']);
        }

        $badge = Arr::get($node, 'attributes.badge');

        if (is_array($badge) && ($badge['data'] ?? null) instanceof \Closure) {
            $node['attributes']['badge']['data'] = ($badge['data'])();
        }

        $list = Arr::get($node, 'attributes.list');

        if (is_iterable($list)) {
            $node['attributes']['list'] = collect($list)
                ->map(static fn ($item) => $item instanceof self ? $item->toArray() : null)
                ->filter()
                ->values()
                ->all();
        }

        return $node;
    }

    /**
     * Set the button's visual style based on the given `Color` enum.
     *
     * This method applies a CSS class to the action element that corresponds to
     * the desired button color, ensuring consistency with the platform's color palette.
     *
     * @param  Color  $visual  The color style to apply to the button.
     * @return static
     */
    public function type(Color $visual): self
    {
        $colors = array_map(static fn (Color $color) => 'btn-'.$color->name(), Color::cases());

        $class = str_replace($colors, '', (string) $this->get('class'));

        $this->set('class', $class.' btn-'.$visual->name());

        return $this;
    }

    /**
     * @return Factory|View|mixed
     *
     * @throws \Throwable
     */
    public function build(?Repository $repository = null)
    {
        return $this->render();
    }

    /**
     * Enable or disable Hotwire Turbo for this action's click event.
     *
     * By setting the `turbo` attribute, this method controls whether
     * Hotwire Turbo should be applied when the action is clicked.
     *
     * @param  bool  $status  Set to `true` to disable Turbo, or `false` to enable it (default).
     * @return static
     */
    public function rawClick(bool $status = false): self
    {
        $this->set('turbo', $status);

        return $this;
    }

    /**
     * Adds the 'stretched-link' class to the element, making its parent block clickable.
     *
     * The `stretched` method appends the 'stretched-link' class to the element's 'class' attribute,
     * allowing the entire parent block of the element to become clickable.
     *
     * Notes: The parent block must have `position: relative`.
     */
    public function stretched(): self
    {
        $this->attributes['class'] .= ' stretched-link';

        return $this;
    }
}
