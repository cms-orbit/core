<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Attachment\Models\Attachment;
use CmsOrbit\Core\Media\MediaLibrary;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST-ish endpoints backing the React media library picker. Endpoint shapes are
 * documented in CONTRACT.md.
 */
class MediaController extends Controller
{
    /**
     * Paginated listing, optionally filtered by kind.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->paginate($request));
    }

    /**
     * Search by name / original name (and optional kind).
     */
    public function search(Request $request): JsonResponse
    {
        return response()->json($this->paginate($request));
    }

    /**
     * Upload one or more files into the library.
     */
    public function upload(Request $request, MediaLibrary $library): JsonResponse
    {
        $request->validate([
            'files' => ['required'],
            'files.*' => ['file'],
        ]);

        $items = collect($request->file('files'))
            ->map(fn ($file) => $this->transform($library->upload($file, $request->input('group', 'media'))))
            ->values();

        return response()->json(['data' => $items], 201);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(string $id): JsonResponse
    {
        $attachment = Orbit::model(Attachment::class)::findOrFail($id);
        $attachment->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Report the (video) encoding status for an attachment.
     */
    public function status(string $id): JsonResponse
    {
        $attachment = Orbit::model(Attachment::class)::findOrFail($id);

        return response()->json([
            'id' => $attachment->getKey(),
            'kind' => $attachment->kind,
            'encoding_status' => $attachment->encoding_status,
            'meta' => $attachment->meta,
        ]);
    }

    /**
     * Build the paginated payload shared by index/search.
     *
     * @return array<string, mixed>
     */
    protected function paginate(Request $request): array
    {
        $query = Orbit::model(Attachment::class)::query()->latest();

        if ($kind = $request->input('kind')) {
            $query->where('kind', $kind);
        }

        if ($term = $request->input('q')) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('original_name', 'like', "%{$term}%");
            });
        }

        $paginator = $query->paginate((int) $request->input('per_page', 40));

        return [
            'data' => collect($paginator->items())->map(fn (Attachment $a) => $this->transform($a))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ];
    }

    /**
     * Serialize an attachment for the picker.
     *
     * @return array<string, mixed>
     */
    protected function transform(Attachment $attachment): array
    {
        return [
            'id' => $attachment->getKey(),
            'url' => $attachment->url(),
            'name' => $attachment->getAttribute('original_name'),
            'kind' => $attachment->kind,
            'mime' => $attachment->mime,
            'extension' => $attachment->extension,
            'size' => $attachment->size,
            'width' => $attachment->width,
            'height' => $attachment->height,
            'duration' => $attachment->duration,
            'alt' => $attachment->alt,
            'encoding_status' => $attachment->encoding_status,
            'created_at' => optional($attachment->created_at)->toIso8601String(),
        ];
    }
}
