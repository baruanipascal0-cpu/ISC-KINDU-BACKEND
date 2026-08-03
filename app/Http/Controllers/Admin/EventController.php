<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $event->update($this->payload($request, $event));

        return redirect()->route('admin.events.index')->with('status', 'Evenement mis a jour.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

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
            'image_file' => ['nullable', 'file', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $imageUrl = $data['image_url'] ?? $event?->image_url;

        if ($request->hasFile('image_file')) {
            $imageUrl = '/storage/'.$request->file('image_file')->store('content/events', 'public');
        }

        return [
            'title' => $data['title'],
            'slug' => $data['slug'] ?: Str::slug($data['title']),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'] ?? null,
            'image_url' => $imageUrl,
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
