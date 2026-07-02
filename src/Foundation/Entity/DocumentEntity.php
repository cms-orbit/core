<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Entity;

use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Fields\CheckBox;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\Quill;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Fields\TextArea;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use Illuminate\Database\Eloquent\Model;

/**
 * A document-aware Entity descriptor. Targets a model built on the Core
 * DocumentModel engine and ships sensible author / locale / content / counter
 * defaults that concrete document types can extend or override.
 */
abstract class DocumentEntity extends Entity
{
    public function icon(): string
    {
        return 'bs.file-earmark-text';
    }

    public function sectionKey(): string
    {
        return 'documents';
    }

    public function section(): string
    {
        return __('Documents');
    }

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('title')->title(__('Title'))->required(),
            Input::make('slug')->title(__('Slug'))->help(__('Leave blank to auto-generate.')),
            Quill::make('content')->title(__('Content')),
            TextArea::make('description')->title(__('Description'))->rows(2),
            Select::make('approved')
                ->title(__('Approval'))
                ->options([0 => __('Rejected'), 10 => __('Waiting'), 30 => __('Approved')]),
            CheckBox::make('is_notice')->title(__('Notice'))->sendTrueOrFalse(),
            CheckBox::make('is_secret')->title(__('Secret'))->sendTrueOrFalse(),
        ];
    }

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('document_id', __('ID'))->sort(),
            TD::make('title', __('Title')),
            TD::make('writer', __('Author')),
            TD::make('read_count', __('Views'))->sort(),
            TD::make('approved', __('Approval')),
            TD::make('created_at', __('Created'))->sort(),
        ];
    }

    /**
     * @return Sight[]
     */
    public function legend(): array
    {
        return [
            Sight::make('document_id', __('ID')),
            Sight::make('title', __('Title')),
            Sight::make('writer', __('Author')),
            Sight::make('read_count', __('Views')),
            Sight::make('assent_count', __('Likes')),
            Sight::make('approved', __('Approval')),
        ];
    }

    /**
     * Default SEO extraction for document content.
     *
     * @return array<string, mixed>
     */
    public function seo(Model $model): array
    {
        return [
            'title' => $model->getAttribute('title'),
            'description' => $model->getAttribute('description')
                ?? $model->getAttribute('pure_content'),
            'thumbnail' => $model->getAttribute('thumbnail'),
            'slug' => $model->getAttribute('slug'),
        ];
    }
}
