<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\PublicUpload;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::orderBy('title')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.form', [
            'page' => new Page(['is_published' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Page::create($this->payload($request));

        return redirect()->route('admin.pages.index')->with('status', 'Page creee.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $oldImage = PublicUpload::imageFrom($page);

        $page->update($this->payload($request, $page));
        PublicUpload::deleteIfReplaced($oldImage, PublicUpload::imageFrom($page));

        return redirect()->route('admin.pages.index')->with('status', 'Page mise a jour.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $image = PublicUpload::imageFrom($page);
        $page->delete();
        PublicUpload::delete($image['image_disk'], $image['image_public_id']);

        return back()->with('status', 'Page supprimee.');
    }

    private function payload(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:190'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $image = $this->imagePayload($request, $data, $page);

        return [
            'title' => $data['title'],
            'slug' => $this->slug($data, $page),
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'image_url' => $image['image_url'],
            'image_public_id' => $image['image_public_id'],
            'image_disk' => $image['image_disk'],
            'image_alt' => $image['image_alt'],
            'is_published' => $request->boolean('is_published'),
        ];
    }

    private function slug(array $data, ?Page $page): string
    {
        $input = trim((string) ($data['slug'] ?? ''));

        if ($input !== '') {
            return UniqueSlug::forModel(Page::class, $input, $page);
        }

        if ($page?->exists && $page->slug) {
            return $page->slug;
        }

        return UniqueSlug::forModel(Page::class, $data['title'], $page);
    }

    private function imagePayload(Request $request, array $data, ?Page $page): array
    {
        $alt = $data['image_alt'] ?? $data['title'] ?? null;

        if ($request->hasFile('image_file')) {
            return PublicUpload::storeImage($request->file('image_file'), 'content/pages', $alt);
        }

        if (! empty($data['image_url'])) {
            return PublicUpload::externalImage($data['image_url'], $alt);
        }

        $current = PublicUpload::imageFrom($page);
        $current['image_alt'] = $alt ?: ($current['image_alt'] ?? null);

        return $current;
    }
}
