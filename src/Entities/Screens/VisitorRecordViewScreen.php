<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Screens;

use CmsOrbit\Core\Crud\Layouts\ResourceFields;
use CmsOrbit\Core\Crud\Requests\ViewRequest;
use CmsOrbit\Core\Crud\Screens\ViewScreen;
use CmsOrbit\Core\Entities\VisitorRecordEntity;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Link;
use CmsOrbit\Core\Support\Facades\Layout;
use LogicException;

class VisitorRecordViewScreen extends ViewScreen
{
    private const RELATED_VISITS_PER_PAGE = 10;

    /**
     * @return array<string, mixed>
     */
    public function query(ViewRequest $request): array
    {
        $this->model = $request->findModelOrFail();
        $entity = $this->visitorEntity();

        return [
            ResourceFields::PREFIX => $this->model,
            'visitorSummary'       => $entity->visitorSummary($this->model),
            'relatedVisits'        => $entity
                ->relatedVisitsQuery($this->model)
                ->paginate(self::RELATED_VISITS_PER_PAGE)
                ->withQueryString(),
        ];
    }

    /**
     * @return Action[]
     */
    public function commandBar(): array
    {
        $entity = $this->entity();
        $key = $entity::uriKey();

        return [
            Link::make(__('Back to list'))
                ->icon('bs.arrow-left')
                ->route('orbit.entities.'.$key.'.index'),
        ];
    }

    /**
     * @return \CmsOrbit\Core\Screen\Layout[]
     */
    public function layout(): array
    {
        if ($this->model === null) {
            return [];
        }

        $entity = $this->visitorEntity();

        return [
            Layout::split([
                Layout::block([
                    Layout::component('VisitorRecordDetailView', $entity->viewDetailProps($this->model)),
                ])->title(__('Visit details')),
                Layout::block([
                    Layout::table('relatedVisits', $entity->historyColumns())
                        ->title(__('Other visits by this visitor'))
                        ->paginationStyle('simple'),
                ])->title(__('Visitor history')),
            ])->ratio('40/60'),
        ];
    }

    protected function visitorEntity(): VisitorRecordEntity
    {
        $entity = $this->entity();

        if (! $entity instanceof VisitorRecordEntity) {
            throw new LogicException('VisitorRecordViewScreen expects the VisitorRecordEntity.');
        }

        return $entity;
    }
}
