<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Program;
use App\Models\Promotion;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentRegistryController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.registry.index', [
            'enrollments' => $this->query($request)
                ->orderedForRegistry()
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->query(),
            'academicYears' => AcademicYear::orderByDesc('is_current')->orderByDesc('starts_on')->get(),
            'sections' => Section::where('is_active', true)->orderBy('sort_order')->get(),
            'programs' => Program::where('is_active', true)->orderBy('name')->get(),
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'promotions' => Promotion::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Enrollment $enrollment): View
    {
        return view('admin.registry.show', [
            'enrollment' => $enrollment->load([
                'student',
                'admissionApplication',
                'academicYear',
                'section',
                'program',
                'level',
                'promotion',
            ]),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $enrollments = $this->query($request)
            ->orderedForRegistry()
            ->get();

        $filename = 'registre-inscriptions-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($enrollments): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Numero',
                'Numero inscription',
                'Matricule',
                'Nom',
                'Postnom',
                'Prenom',
                'Sexe',
                'Annee academique',
                'Section',
                'Option/Filiere',
                'Promotion',
                'Date',
                'Statut',
            ], ';');

            foreach ($enrollments as $index => $enrollment) {
                fputcsv($handle, [
                    $index + 1,
                    $enrollment->enrollment_number,
                    $enrollment->student?->matricule,
                    $enrollment->student?->last_name,
                    $enrollment->student?->post_name,
                    $enrollment->student?->first_name,
                    $enrollment->student?->gender,
                    $enrollment->academicYear?->code,
                    $enrollment->section?->name,
                    $enrollment->program?->name,
                    $enrollment->promotion?->name ?? $enrollment->level?->name,
                    $enrollment->enrolled_on?->toDateString(),
                    $enrollment->status,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function query(Request $request): Builder
    {
        return Enrollment::query()
            ->with(['student', 'academicYear', 'section', 'program', 'level', 'promotion', 'admissionApplication'])
            ->when($request->query('academic_year_id'), fn (Builder $query, string $id) => $query->where('academic_year_id', $id))
            ->when($request->query('section_id'), fn (Builder $query, string $id) => $query->where('section_id', $id))
            ->when($request->query('program_id'), fn (Builder $query, string $id) => $query->where('program_id', $id))
            ->when($request->query('promotion_id'), fn (Builder $query, string $id) => $query->where('promotion_id', $id))
            ->when($request->query('level_id'), fn (Builder $query, string $id) => $query->where('level_id', $id))
            ->when($request->query('type'), fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($request->query('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->query('date_from'), fn (Builder $query, string $date) => $query->whereDate('enrolled_on', '>=', $date))
            ->when($request->query('date_to'), fn (Builder $query, string $date) => $query->whereDate('enrolled_on', '<=', $date))
            ->when($request->query('gender'), function (Builder $query, string $gender): void {
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('gender', $gender));
            })
            ->when($request->query('search'), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('enrollment_number', 'like', '%'.$search.'%')
                        ->orWhereHas('student', function (Builder $studentQuery) use ($search): void {
                            $studentQuery->where('matricule', 'like', '%'.$search.'%')
                                ->orWhere('last_name', 'like', '%'.$search.'%')
                                ->orWhere('post_name', 'like', '%'.$search.'%')
                                ->orWhere('first_name', 'like', '%'.$search.'%');
                        });
                });
            });
    }
}
