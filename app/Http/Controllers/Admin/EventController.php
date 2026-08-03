<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\PublicUpload;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('admin.events.index', [
            'events' => Event::orderByDesc('starts_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.form', [
            'event' => new Event(['is_published' => true, 'starts_at' => now()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Event::create($this->payload($request));

        return redirect()->route('admin.events.index')->with('status', 'Evenement cree.');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $oldImage = PublicUpload::imageFrom($event);

        $event->update($this->payload($request, $event));
        PublicUpload::deleteIfReplaced($oldImage, PublicUpload::imageFrom($event));

        return redirect()->route('admin.events.index')->with('status', 'Evenement mis a jour.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $image = PublicUpload::imageFrom($event);
        $event->delete();
        PublicUpload::delete($image['image_disk'], $image['image_public_id']);

        return back()->with('status', 'Evenement supprime.');
    }

    private function payload(Request $request, ?Event $event = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:190'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:190'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $image = $this->imagePayload($request, $data, $event);

        return [
            'title' => $data['title'],
            'slug' => $this->slug($data, $event),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'] ?? null,
            'image_url' => $image['image_url'],
            'image_public_id' => $image['image_public_id'],
            'image_disk' => $image['image_disk'],
            'image_alt' => $image['image_alt'],
            'is_published' => $request->boolean('is_published'),
        ];
    }

    private function slug(array $data, ?Event $event): string
    {
        $input = trim((string) ($data['slug'] ?? ''));

        if ($input !== '') {
            return UniqueSlug::forModel(Event::class, $input, $event);
        }

        if ($event?->exists && $event->slug) {
            return $event->slug;
        }

        return UniqueSlug::forModel(Event::class, $data['title'], $event);
    }

    private function imagePayload(Request $request, array $data, ?Event $event): array
    {
        $alt = $data['image_alt'] ?? $data['title'] ?? null;

        if ($request->hasFile('image_file')) {
            return PublicUpload::storeImage($request->file('image_file'), 'content/events', $alt);
        }

        if (! empty($data['image_url'])) {
            return PublicUpload::externalImage($data['image_url'], $alt);
        }

        $current = PublicUpload::imageFrom($event);
        $current['image_alt'] = $alt ?: ($current['image_alt'] ?? null);

        return $current;
    }
}
