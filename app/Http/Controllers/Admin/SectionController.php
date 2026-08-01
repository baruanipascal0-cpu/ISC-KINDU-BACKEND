<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(): View
    {
        return view('admin.sections.index', [
            'sections' => Section::with('programs')->orderBy('sort_order')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.sections.form', [
            'section' => new Section(['is_active' => true]),
            'programsText' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->payload($request);
        $section = Section::create($payload['section']);
        $this->syncPrograms($section, $payload['programs_text']);

        return redirect()->route('admin.sections.index')->with('status', 'Section creee.');
    }

    public function edit(Section $section): View
    {
        $section->load('programs');

        return view('admin.sections.form', [
            'section' => $section,
            'programsText' => $section->programs
                ->map(fn (Program $program) => $program->name.' | '.$program->cycle.' | '.$program->description)
                ->implode("\n"),
        ]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $payload = $this->payload($request, $section);
        $section->update($payload['section']);
        $this->syncPrograms($section, $payload['programs_text']);

        return redirect()->route('admin.sections.index')->with('status', 'Section mise a jour.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        $section->delete();

        return back()->with('status', 'Section supprimee.');
    }

    private function payload(Request $request, ?Section $section = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'programs_text' => ['nullable', 'string', 'max:10000'],
        ]);

        return [
            'section' => [
                'name' => $data['name'],
                'slug' => $data['slug'] ?: ($section?->slug ?: Str::slug($data['name'])),
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
            ],
            'programs_text' => $data['programs_text'] ?? '',
        ];
    }

    private function syncPrograms(Section $section, string $programsText): void
    {
        collect(preg_split('/\R/', $programsText))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->each(function (string $line) use ($section): void {
                [$name, $cycle, $description] = array_pad(array_map('trim', explode('|', $line, 3)), 3, null);

                Program::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'section_id' => $section->id,
                        'name' => $name,
                        'cycle' => $cycle ?: 'Licence',
                        'description' => $description ?: 'Description a completer.',
                        'is_active' => true,
                    ]
                );
            });
    }
}
