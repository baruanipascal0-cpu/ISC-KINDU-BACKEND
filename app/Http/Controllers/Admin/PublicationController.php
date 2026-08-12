<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Support\PublicUpload;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicationController extends Controller
{
    public function index(): View
    {
        $type = request('type');

        return view('admin.publications.index', [
            'publications' => Publication::query()
                ->when($type, fn ($query, string $type) => $query->where('type', $type))
                ->latest('published_at')
                ->paginate(20)
                ->withQueryString(),
            'type' => $type,
            'publicationTypeGroups' => config('isc_site.publication_type_groups', []),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.publications.form', [
            'publication' => new Publication([
                'type' => $request->query('type', 'Document'),
                'is_published' => true,
                'published_at' => now(),
            ]),
            'publicationTypeGroups' => config('isc_site.publication_type_groups', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Publication::create($this->payload($request));

        return redirect()
            ->route('admin.publications.index', ['type' => $request->input('type')])
            ->with('status', 'Publication creee.');
    }

    public function edit(Publication $publication): View
    {
        return view('admin.publications.form', [
            'publication' => $publication,
            'publicationTypeGroups' => config('isc_site.publication_type_groups', []),
        ]);
    }

    public function update(Request $request, Publication $publication): RedirectResponse
    {
        $oldImage = PublicUpload::imageFrom($publication);

        $publication->update($this->payload($request, $publication));
        PublicUpload::deleteIfReplaced($oldImage, PublicUpload::imageFrom($publication));

        return redirect()
            ->route('admin.publications.index', ['type' => $request->input('type')])
            ->with('status', 'Publication mise a jour.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        $image = PublicUpload::imageFrom($publication);
        $publication->delete();
        PublicUpload::delete($image['image_disk'], $image['image_public_id']);

        return back()->with('status', 'Publication supprimee.');
    }

    private function payload(Request $request, ?Publication $publication = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:190'],
            'file_url' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'max:10240'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $fileUrl = $data['file_url'] ?? $publication?->file_url;

        if ($request->hasFile('file')) {
            $fileUrl = PublicUpload::store($request->file('file'), 'content/publications');
        }

        $image = $this->imagePayload($request, $data, $publication);

        return [
            'title' => $data['title'],
            'slug' => $this->slug($data, $publication),
            'type' => $data['type'] ?? 'Document',
            'description' => $data['description'] ?? null,
            'image_url' => $image['image_url'],
            'image_public_id' => $image['image_public_id'],
            'image_disk' => $image['image_disk'],
            'image_alt' => $image['image_alt'],
            'file_url' => $fileUrl,
            'published_at' => $data['published_at'] ?? now(),
            'is_published' => $request->boolean('is_published'),
        ];
    }

    private function slug(array $data, ?Publication $publication): string
    {
        $input = trim((string) ($data['slug'] ?? ''));

        if ($input !== '') {
            return UniqueSlug::forModel(Publication::class, $input, $publication);
        }

        if ($publication?->exists && $publication->slug) {
            return $publication->slug;
        }

        return UniqueSlug::forModel(Publication::class, $data['title'], $publication);
    }

    private function imagePayload(Request $request, array $data, ?Publication $publication): array
    {
        $alt = $data['image_alt'] ?? $data['title'] ?? null;

        if ($request->hasFile('image_file')) {
            return PublicUpload::storeImage($request->file('image_file'), 'content/publications/images', $alt);
        }

        if (! empty($data['image_url'])) {
            return PublicUpload::externalImage($data['image_url'], $alt);
        }

        $current = PublicUpload::imageFrom($publication);
        $current['image_alt'] = $alt ?: ($current['image_alt'] ?? null);

        return $current;
    }
}
