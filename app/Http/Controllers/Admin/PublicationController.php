<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Support\PublicUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.publications.form', [
            'publication' => new Publication([
                'type' => $request->query('type', 'Communique'),
                'is_published' => true,
                'published_at' => now(),
            ]),
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
        return view('admin.publications.form', compact('publication'));
    }

    public function update(Request $request, Publication $publication): RedirectResponse
    {
        $publication->update($this->payload($request, $publication));

        return redirect()
            ->route('admin.publications.index', ['type' => $request->input('type')])
            ->with('status', 'Publication mise a jour.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        $publication->delete();

        return back()->with('status', 'Publication supprimee.');
    }

    private function payload(Request $request, ?Publication $publication = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'file_url' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'max:10240'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $fileUrl = $data['file_url'] ?? $publication?->file_url;

        if ($request->hasFile('file')) {
            $fileUrl = PublicUpload::store($request->file('file'), 'content/publications');
        }

        return [
            'title' => $data['title'],
            'slug' => $data['slug'] ?: Str::slug($data['title']),
            'type' => $data['type'] ?? 'Document',
            'description' => $data['description'] ?? null,
            'file_url' => $fileUrl,
            'published_at' => $data['published_at'] ?? now(),
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
