<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Graduate;
use App\Models\GraduationList;
use App\Models\Program;
use App\Models\Promotion;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GraduationListController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.graduations.index', [
            'lists' => GraduationList::query()
                ->with(['academicYear', 'section', 'program', 'promotion'])
                ->withCount('graduates')
                ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
                ->when($request->query('academic_year_id'), fn ($query, string $id) => $query->where('academic_year_id', $id))
                ->when($request->query('section_id'), fn ($query, string $id) => $query->where('section_id', $id))
                ->when($request->query('program_id'), fn ($query, string $id) => $query->where('program_id', $id))
                ->when($request->query('promotion_id'), fn ($query, string $id) => $query->where('promotion_id', $id))
                ->latest('published_at')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->query(),
            'academicYears' => AcademicYear::orderByDesc('is_current')->orderByDesc('starts_on')->get(),
            'sections' => Section::where('is_active', true)->orderBy('sort_order')->get(),
            'programs' => Program::where('is_active', true)->orderBy('name')->get(),
            'promotions' => Promotion::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.graduations.form', [
            'graduationList' => new GraduationList([
                'status' => 'draft',
                'published_at' => now(),
            ]),
            'graduatesText' => '',
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedPayload($request);

        DB::transaction(function () use ($data, $request): void {
            $list = GraduationList::create($data);
            $this->syncGraduates($list, (string) $request->input('graduates_text', ''));
        });

        return redirect()->route('admin.graduations.index')->with('status', 'Liste des diplomes creee.');
    }

    public function show(GraduationList $graduationList): View
    {
        return view('admin.graduations.show', [
            'graduationList' => $graduationList->load([
                'academicYear',
                'section',
                'program',
                'promotion',
                'graduates' => fn ($query) => $query->orderBy('sort_order')->orderBy('last_name'),
            ]),
        ]);
    }

    public function edit(GraduationList $graduationList): View
    {
        $graduationList->load(['graduates' => fn ($query) => $query->orderBy('sort_order')->orderBy('last_name')]);

        return view('admin.graduations.form', [
            'graduationList' => $graduationList,
            'graduatesText' => $graduationList->graduates->map(function (Graduate $graduate): string {
                return implode(';', [
                    $graduate->matricule,
                    $graduate->last_name,
                    $graduate->post_name,
                    $graduate->first_name,
                    $graduate->gender,
                    $graduate->percentage,
                    $graduate->mention,
                ]);
            })->implode("\n"),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, GraduationList $graduationList): RedirectResponse
    {
        $data = $this->validatedPayload($request, $graduationList);

        DB::transaction(function () use ($graduationList, $data, $request): void {
            $graduationList->update($data);
            $this->syncGraduates($graduationList, (string) $request->input('graduates_text', ''));
        });

        return redirect()->route('admin.graduations.index')->with('status', 'Liste des diplomes mise a jour.');
    }

    public function destroy(GraduationList $graduationList): RedirectResponse
    {
        $graduationList->delete();

        return back()->with('status', 'Liste des diplomes supprimee.');
    }

    public function exportCsv(GraduationList $graduationList): StreamedResponse
    {
        $graduationList->load([
            'graduates' => fn ($query) => $query->orderBy('sort_order')->orderBy('last_name'),
        ]);

        $filename = 'diplomes-'.$graduationList->slug.'.csv';

        return response()->streamDownload(function () use ($graduationList): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Numero', 'Matricule', 'Nom', 'Postnom', 'Prenom', 'Sexe', 'Pourcentage', 'Mention'], ';');

            foreach ($graduationList->graduates as $index => $graduate) {
                fputcsv($handle, [
                    $index + 1,
                    $graduate->matricule,
                    $graduate->last_name,
                    $graduate->post_name,
                    $graduate->first_name,
                    $graduate->gender,
                    $graduate->percentage,
                    $graduate->mention,
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedPayload(Request $request, ?GraduationList $graduationList = null): array
    {
        $data = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'cycle' => ['nullable', 'string', 'max:80'],
            'decision_date' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'graduates_text' => ['nullable', 'string'],
        ]);

        $slug = ($data['slug'] ?? null) ?: Str::slug($data['title']);

        return [
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'program_id' => $data['program_id'] ?? null,
            'promotion_id' => $data['promotion_id'] ?? null,
            'title' => $data['title'],
            'slug' => $slug,
            'cycle' => $data['cycle'] ?? null,
            'decision_date' => $data['decision_date'] ?? null,
            'published_at' => $data['published_at'] ?? ($graduationList?->published_at ?? now()),
            'status' => $data['status'],
        ];
    }

    private function syncGraduates(GraduationList $list, string $text): void
    {
        $list->graduates()->delete();

        collect(preg_split('/\r\n|\r|\n/', trim($text)))
            ->filter(fn (string $line) => trim($line) !== '')
            ->values()
            ->each(function (string $line, int $index) use ($list): void {
                $parts = array_map('trim', preg_split('/[;\t|,]/', $line));

                if (count($parts) === 1) {
                    $names = preg_split('/\s+/', $parts[0], 3);
                    $parts = [
                        null,
                        $names[0] ?? '',
                        $names[1] ?? null,
                        $names[2] ?? '',
                        null,
                        null,
                        null,
                    ];
                }

                Graduate::create([
                    'graduation_list_id' => $list->id,
                    'matricule' => $parts[0] ?: null,
                    'last_name' => $parts[1] ?? 'Nom',
                    'post_name' => $parts[2] ?? null,
                    'first_name' => $parts[3] ?? '',
                    'gender' => $parts[4] ?? null,
                    'percentage' => is_numeric($parts[5] ?? null) ? $parts[5] : null,
                    'mention' => $parts[6] ?? null,
                    'sort_order' => $index + 1,
                ]);
            });
    }

    private function formOptions(): array
    {
        return [
            'academicYears' => AcademicYear::orderByDesc('is_current')->orderByDesc('starts_on')->get(),
            'sections' => Section::where('is_active', true)->orderBy('sort_order')->get(),
            'programs' => Program::where('is_active', true)->orderBy('name')->get(),
            'promotions' => Promotion::where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }
}
