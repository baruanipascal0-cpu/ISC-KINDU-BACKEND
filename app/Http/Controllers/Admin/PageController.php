<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $page->update($this->payload($request, $page));

        return redirect()->route('admin.pages.index')->with('status', 'Page mise a jour.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

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
            'is_published' => ['nullable', 'boolean'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['title']);

        return [
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'image_url' => $data['image_url'] ?? $page?->image_url,
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
