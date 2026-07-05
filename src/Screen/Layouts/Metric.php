<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

/**
 * Class Metric.
 */
class Metric extends Layout
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.metric';

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var array
     */
    protected $labels = [];

    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }

    /**
     * @return Factory|View
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (! $this->isSee() || empty($this->labels)) {
            return;
        }

        $metrics = collect($this->labels)->map(fn (string $value) => $repository->getContent($value, ''));

        return view($this->template, [
            'title'   => $this->title,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Serialize the metric cards to the React contract.
     *
     * Each label maps a display label to a repository key. The resolved
     * content is normalized to `{ label, value, diff }` so the React
     * MetricLayout can render the value (and optional diff badge).
     *
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        $metrics = collect($this->labels)
            ->map(function (string $key, string $label) use ($repository) {
                $content = $repository->getContent($key, '');

                if (is_array($content)) {
                    return [
                        'label'      => $label,
                        'value'      => $content['value'] ?? null,
                        'diff'       => $content['diff'] ?? null,
                        'detail'     => $content['detail'] ?? null,
                        'detailTone' => $content['detailTone'] ?? null,
                    ];
                }

                return [
                    'label' => $label,
                    'value' => $content,
                    'diff'  => null,
                ];
            })
            ->values()
            ->all();

        return [
            'title'   => $this->title,
            'metrics' => $metrics,
        ];
    }

    /**
     * @return $this
     */
    public function title(string $title): Metric
    {
        $this->title = $title;

        return $this;
    }
}
