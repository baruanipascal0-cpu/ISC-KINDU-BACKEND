<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Support\PublicUpload;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $collection = $request->query('collection');
        $collections = MediaFile::query()
            ->select('collection')
            ->distinct()
            ->orderBy('collection')
            ->pluck('collection')
            ->filter()
            ->values();

        return view('admin.media.index', [
            'mediaFiles' => MediaFile::query()
                ->when($collection, fn ($query, string $collection) => $query->where('collection', $collection))
                ->orderBy('collection')
                ->orderBy('sort_order')
                ->latest('published_at')
                ->paginate(20)
                ->withQueryString(),
            'collection' => $collection,
            'collections' => $collections,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.media.form', [
            'media' => new MediaFile([
                'collection' => $request->query('collection', 'gallery'),
                'is_published' => true,
                'published_at' => now(),
                'sort_order' => 0,
            ]),
            'metadataText' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $media = MediaFile::create($this->payload($request));

        return redirect()
            ->route('admin.media.index', ['collection' => $media->collection])
            ->with('status', 'Media ajoute.');
    }

    public function edit(MediaFile $media): View
    {
        return view('admin.media.form', [
            'media' => $media,
            'metadataText' => $media->metadata ? json_encode($media->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
        ]);
    }

    public function update(Request $request, MediaFile $media): RedirectResponse
    {
        $oldDisk = $media->disk;
        $oldPublicId = $media->public_id;

        $media->update($this->payload($request, $media));

        if ($oldPublicId !== $media->public_id) {
            PublicUpload::delete($oldDisk, $oldPublicId);
        }

        return redirect()
            ->route('admin.media.index', ['collection' => $media->collection])
            ->with('status', 'Media mis a jour.');
    }

    public function destroy(MediaFile $media): RedirectResponse
    {
        PublicUpload::delete($media->disk, $media->public_id);
        $media->delete();

        return back()->with('status', 'Media supprime.');
    }

    private function payload(Request $request, ?MediaFile $media = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'collection' => ['required', 'string', 'max:120'],
            'caption' => ['nullable', 'string'],
            'alt_text' => ['nullable', 'string', 'max:190'],
            'file' => ['nullable', 'file', 'max:51200'],
            'path' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'json'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $path = $data['path'] ?? $media?->path;
        $disk = $media?->disk;
        $publicId = $media?->public_id;
        $mimeType = $media?->mime_type;
        $size = $media?->size ?? 0;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $stored = PublicUpload::storeImage($file, 'content/media', $data['alt_text'] ?? $data['name']);
            $path = $stored['image_url'];
            $disk = $stored['image_disk'];
            $publicId = $stored['image_public_id'];
            $mimeType = $file->getMimeType();
            $size = $file->getSize() ?: 0;
        } elseif (! empty($data['path']) && $data['path'] !== $media?->path) {
            $disk = str_starts_with($data['path'], 'http://') || str_starts_with($data['path'], 'https://') ? 'external' : null;
            $publicId = null;
        }

        return [
            'user_id' => $request->user()?->id ?? $media?->user_id,
            'name' => $data['name'],
            'slug' => $this->slug($data, $media),
            'collection' => $data['collection'],
            'caption' => $data['caption'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'path' => $path,
            'disk' => $disk,
            'public_id' => $publicId,
            'mime_type' => $mimeType,
            'size' => $size,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $data['published_at'] ?? now(),
            'sort_order' => $data['sort_order'] ?? 0,
            'metadata' => isset($data['metadata']) && $data['metadata'] !== '' ? json_decode($data['metadata'], true) : null,
        ];
    }

    private function slug(array $data, ?MediaFile $media): string
    {
        $input = trim((string) ($data['slug'] ?? ''));

        if ($input !== '') {
            return UniqueSlug::forModel(MediaFile::class, $input, $media);
        }

        if ($media?->exists && $media->slug) {
            return $media->slug;
        }

        return UniqueSlug::forModel(MediaFile::class, $data['name'], $media);
    }
}
