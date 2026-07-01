<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Attachment\File;
use CmsOrbit\Core\Attachment\Models\Attachment;
use CmsOrbit\Core\Foundation\Events\UploadedFileEvent;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AttachmentController.
 */
class AttachmentController extends Controller
{
    /**
     * @var Attachment
     */
    protected $attachment;

    /**
     * AttachmentController constructor.
     */
    public function __construct()
    {
        $this->checkPermission('orbit.attachment');
        $this->attachment = Orbit::modelClass(Attachment::class);
    }

    /**
     * Upload files and return their details.
     */
    public function upload(Request $request): JsonResponse
    {
        $attachment = collect($request->allFiles())
            ->flatten()
            ->map(fn (UploadedFile $file) => $this->createModel($file, $request));

        $response = $attachment->count() > 1 ? $attachment : $attachment->first();

        return response()->json($response);
    }

    /**
     * Update the sort order of the files.
     */
    public function sort(Request $request): void
    {
        collect($request->input('files', []))
            ->each(function ($sort, $id) {
                $attachment = $this->attachment->find($id);
                $attachment->sort = $sort;
                $attachment->save();
            });
    }

    /**
     * Delete files.
     */
    public function destroy(string $id, Request $request): void
    {
        $storage = $request->input('storage', 'public');
        $this->attachment->findOrFail($id)->delete($storage);
    }

    /**
     * @return ResponseFactory|Response
     */
    public function update(string $id, Request $request)
    {
        $attachment = $this->attachment
            ->findOrFail($id)
            ->fill($request->all());

        $attachment->save();

        return response()->json($attachment);
    }

    /**
     * Create and load an attachment model from the uploaded file.
     *
     *
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    private function createModel(UploadedFile $file, Request $request)
    {
        $file = resolve(File::class, [
            'file' => $file,
            'disk' => $request->input('storage'),
            'group' => $request->input('group'),
        ]);

        if ($request->has('path')) {
            $file->path($request->input('path'));
        }

        if ($request->has('sort')) {
            $file->sort($request->integer('sort'));
        }

        $model = $file->load();

        $model->url = $model->url();

        event(new UploadedFileEvent($model));

        return $model;
    }

    /**
     * Retrieve paginated media attachments.
     */
    public function media(): JsonResponse
    {
        $attachments = $this->attachment->filters()->paginate(12);

        return response()->json($attachments);
    }
}
