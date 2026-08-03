<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use App\Support\PublicUpload;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('admin.news.index', [
            'posts' => NewsPost::latest('published_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.news.form', [
            'post' => new NewsPost(['is_published' => true, 'published_at' => now()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        NewsPost::create($this->payload($request));

        return redirect()->route('admin.news.index')->with('status', 'Actualite publiee.');
    }

    public function edit(NewsPost $news): View
    {
        return view('admin.news.form', ['post' => $news]);
    }

    public function update(Request $request, NewsPost $news): RedirectResponse
    {
        $oldImage = PublicUpload::imageFrom($news);

        $news->update($this->payload($request, $news));
        PublicUpload::deleteIfReplaced($oldImage, PublicUpload::imageFrom($news));

        return redirect()->route('admin.news.index')->with('status', 'Actualite mise a jour.');
    }

    public function destroy(NewsPost $news): RedirectResponse
    {
        $image = PublicUpload::imageFrom($news);
        $news->delete();
        PublicUpload::delete($image['image_disk'], $image['image_public_id']);

        return back()->with('status', 'Actualite supprimee.');
    }

    private function payload(Request $request, ?NewsPost $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'category' => ['nullable', 'string', 'max:120'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:190'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $image = $this->imagePayload($request, $data, $post);

        return [
            'title' => $data['title'],
            'slug' => $this->slug($data, $post),
            'category' => $data['category'] ?? 'Actualites',
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'image_url' => $image['image_url'],
            'image_public_id' => $image['image_public_id'],
            'image_disk' => $image['image_disk'],
            'image_alt' => $image['image_alt'],
            'published_at' => $data['published_at'] ?? now(),
            'is_published' => $request->boolean('is_published'),
        ];
    }

    private function slug(array $data, ?NewsPost $post): string
    {
        $input = trim((string) ($data['slug'] ?? ''));

        if ($input !== '') {
            return UniqueSlug::forModel(NewsPost::class, $input, $post);
        }

        if ($post?->exists && $post->slug) {
            return $post->slug;
        }

        return UniqueSlug::forModel(NewsPost::class, $data['title'], $post);
    }

    private function imagePayload(Request $request, array $data, ?NewsPost $post): array
    {
        $alt = $data['image_alt'] ?? $data['title'] ?? null;

        if ($request->hasFile('image_file')) {
            return PublicUpload::storeImage($request->file('image_file'), 'content/news', $alt);
        }

        if (! empty($data['image_url'])) {
            return PublicUpload::externalImage($data['image_url'], $alt);
        }

        $current = PublicUpload::imageFrom($post);
        $current['image_alt'] = $alt ?: ($current['image_alt'] ?? null);

        return $current;
    }
}
