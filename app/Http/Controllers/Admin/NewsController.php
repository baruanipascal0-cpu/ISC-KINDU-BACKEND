<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $news->update($this->payload($request, $news));

        return redirect()->route('admin.news.index')->with('status', 'Actualite mise a jour.');
    }

    public function destroy(NewsPost $news): RedirectResponse
    {
        $news->delete();

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
            'image_file' => ['nullable', 'file', 'max:5120'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $imageUrl = $data['image_url'] ?? $post?->image_url;

        if ($request->hasFile('image_file')) {
            $imageUrl = '/storage/'.$request->file('image_file')->store('content/news', 'public');
        }

        return [
            'title' => $data['title'],
            'slug' => $data['slug'] ?: Str::slug($data['title']),
            'category' => $data['category'] ?? 'Actualites',
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'image_url' => $imageUrl,
            'published_at' => $data['published_at'] ?? now(),
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
