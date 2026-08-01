<?php

namespace App\Http\Controllers\Api;

use App\Models\AdmissionApplication;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Program;
use App\Models\Promotion;
use App\Models\Section;
use App\Services\AdmissionWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdmissionController extends ApiController
{
    public function __construct(private readonly AdmissionWorkflowService $workflow)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $existingApplication = AdmissionApplication::query()
            ->with(['section', 'program', 'academicYear', 'academicLevel', 'promotion'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        if ($existingApplication) {
            $nextStep = in_array($existingApplication->status, ['draft', 'needs_correction'], true)
                ? 'admission_form'
                : 'student_wallet';

            return response()->json([
                'success' => false,
                'message' => 'Ce compte possede deja un dossier d admission.',
                'data' => [
                    'application' => $this->applicationResource($existingApplication),
                    'next_step' => $nextStep,
                ],
            ], 409);
        }

        $data = $this->validatedApplication($request);
        [$section, $program] = $this->resolveSectionAndProgram($data);
        [$academicYear, $level, $promotion] = $this->resolveAcademicChoice($data);

        $application = AdmissionApplication::create($this->applicationPayload($request, $data, $section, $program, $academicYear, $level, $promotion) + [
            'application_number' => $this->nextApplicationNumber(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->registerApplicationDocuments($request, $application);

        $application->load(['section', 'program', 'academicYear', 'academicLevel', 'promotion', 'applicationDocuments.documentType']);

        return $this->ok($this->applicationResource($application), 'Dossier d inscription envoye.', 201);
    }

    public function current(Request $request): JsonResponse
    {
        $application = AdmissionApplication::query()
            ->with([
                'section',
                'program',
                'academicYear',
                'academicLevel',
                'promotion',
                'applicationDocuments.documentType',
                'decisions',
                'student.enrollments.academicYear',
                'student.enrollments.section',
                'student.enrollments.program',
                'student.enrollments.level',
                'student.enrollments.promotion',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        return $this->ok([
            'application' => $application ? $this->applicationResource($application) : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $application = AdmissionApplication::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->firstOrFail();

        abort_if(! in_array($application->status, ['draft', 'needs_correction'], true), 409, 'Ce dossier ne peut plus etre modifie.');

        $data = $this->validatedApplication($request, partial: true);
        [$section, $program] = $this->resolveSectionAndProgram($data, $application);
        [$academicYear, $level, $promotion] = $this->resolveAcademicChoice($data, $application);

        $application->update($this->applicationPayload($request, $data, $section, $program, $academicYear, $level, $promotion, $application, partial: true) + [
            'status' => 'submitted',
            'submitted_at' => $application->submitted_at ?? now(),
            'student_message' => null,
        ]);

        $this->registerApplicationDocuments($request, $application);

        $application->load(['section', 'program', 'academicYear', 'academicLevel', 'promotion', 'applicationDocuments.documentType']);

        return $this->ok($this->applicationResource($application), 'Dossier d inscription mis a jour.');
    }

    public function status(Request $request): JsonResponse
    {
        $application = AdmissionApplication::query()
            ->with([
                'section',
                'program',
                'academicYear',
                'academicLevel',
                'promotion',
                'applicationDocuments.documentType',
                'decisions',
                'student.enrollments',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        return $this->ok([
            'status' => $application?->status ?? 'not_started',
            'application' => $application ? $this->applicationResource($application) : null,
        ]);
    }

    private function validatedApplication(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'academic_year_id' => ['sometimes', 'integer', 'exists:academic_years,id'],
            'academic_year' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:20'],
            'level_id' => ['sometimes', 'integer', 'exists:levels,id'],
            'level' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:80'],
            'promotion_id' => ['sometimes', 'integer', 'exists:promotions,id'],
            'promotion' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:120'],
            'section_id' => ['sometimes', 'integer', 'exists:sections,id'],
            'section' => [$required, 'string', 'max:160'],
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'program' => [$required, 'string', 'max:160'],
            'last_name' => [$required, 'string', 'max:120'],
            'post_name' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:120'],
            'middle_name' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:120'],
            'first_name' => [$required, 'string', 'max:120'],
            'gender' => [$partial ? 'sometimes' : 'nullable', Rule::in(['M', 'F', 'Autre'])],
            'nationality' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:120'],
            'email' => [$required, 'email', 'max:190'],
            'phone' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:40'],
            'address' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:500'],
            'birth_date' => [$partial ? 'sometimes' : 'nullable', 'date'],
            'birth_place' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:120'],
            'last_school' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:190'],
            'diploma_year' => [$partial ? 'sometimes' : 'nullable', 'integer', 'min:1950', 'max:'.((int) now()->year + 1)],
            'percentage' => [$partial ? 'sometimes' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'guardian_phone' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:40'],
            'comment' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:2000'],
            'diploma_file' => [$partial ? 'sometimes' : 'nullable', 'file', 'max:5120'],
            'photo_file' => [$partial ? 'sometimes' : 'nullable', 'file', 'max:5120'],
            'institution_code' => ['sometimes', Rule::in(['ISC_KINDU'])],
        ]);
    }

    private function resolveSectionAndProgram(array $data, ?AdmissionApplication $application = null): array
    {
        $section = isset($data['section_id'])
            ? Section::find($data['section_id'])
            : null;

        if (! $section && isset($data['section'])) {
            $section = Section::query()
                ->where('slug', Str::slug($data['section']))
                ->orWhere('name', $data['section'])
                ->first();
        }

        $section ??= $application?->section;

        $program = isset($data['program_id'])
            ? Program::find($data['program_id'])
            : null;

        if (! $program && isset($data['program'])) {
            $program = Program::query()
                ->where('slug', Str::slug($data['program']))
                ->orWhere('name', $data['program'])
                ->first();
        }

        $program ??= $application?->program;

        abort_if(! $section, 422, 'La section demandee n existe pas dans ISC KINDU.');
        abort_if(! $program, 422, 'La filiere demandee n existe pas dans ISC KINDU.');
        abort_if($program->section_id !== $section->id, 422, 'La filiere ne correspond pas a la section choisie.');

        return [$section, $program];
    }

    private function resolveAcademicChoice(array $data, ?AdmissionApplication $application = null): array
    {
        $academicYear = isset($data['academic_year_id'])
            ? AcademicYear::find($data['academic_year_id'])
            : null;

        if (! $academicYear && isset($data['academic_year'])) {
            $academicYear = AcademicYear::query()
                ->where('code', $data['academic_year'])
                ->orWhere('name', $data['academic_year'])
                ->first();
        }

        $academicYear ??= $application?->academicYear;
        $academicYear ??= AcademicYear::where('is_current', true)->first();

        $level = isset($data['level_id'])
            ? Level::find($data['level_id'])
            : null;

        if (! $level && isset($data['level'])) {
            $level = Level::query()
                ->where('code', $data['level'])
                ->orWhere('name', $data['level'])
                ->first();
        }

        $level ??= $application?->academicLevel;

        $promotion = isset($data['promotion_id'])
            ? Promotion::find($data['promotion_id'])
            : null;

        if (! $promotion && isset($data['promotion'])) {
            $promotion = Promotion::query()
                ->where('code', $data['promotion'])
                ->orWhere('name', $data['promotion'])
                ->first();
        }

        $promotion ??= $application?->promotion;

        if ($promotion && ! $level) {
            $level = $promotion->level;
        }

        if (! $promotion && $level) {
            $promotion = Promotion::query()
                ->where('level_id', $level->id)
                ->orderBy('sort_order')
                ->first();
        }

        return [$academicYear, $level, $promotion];
    }

    private function applicationPayload(
        Request $request,
        array $data,
        Section $section,
        Program $program,
        ?AcademicYear $academicYear,
        ?Level $level,
        ?Promotion $promotion,
        ?AdmissionApplication $application = null,
        bool $partial = false
    ): array
    {
        $payload = [
            'user_id' => $request->user()->id,
            'section_id' => $section->id,
            'program_id' => $program->id,
            'academic_year_id' => $academicYear?->id,
            'level_id' => $level?->id,
            'promotion_id' => $promotion?->id,
            'academic_year' => $academicYear?->code ?? $data['academic_year'] ?? $application?->academic_year ?? $this->academicYear(),
            'level' => $level?->code ?? $data['level'] ?? 'L1',
            'post_name' => $data['post_name'] ?? $data['middle_name'] ?? null,
        ];

        foreach ([
            'last_name',
            'first_name',
            'gender',
            'nationality',
            'email',
            'phone',
            'address',
            'birth_date',
            'birth_place',
            'last_school',
            'diploma_year',
            'percentage',
            'guardian_phone',
            'comment',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if ($request->hasFile('diploma_file')) {
            $payload['diploma_path'] = $request->file('diploma_file')->store('admissions/diplomas', 'public');
        }

        if ($request->hasFile('photo_file')) {
            $payload['photo_path'] = $request->file('photo_file')->store('admissions/photos', 'public');
        }

        if ($partial) {
            return array_filter($payload, fn ($value) => $value !== null);
        }

        return $payload;
    }

    private function registerApplicationDocuments(Request $request, AdmissionApplication $application): void
    {
        if ($request->hasFile('diploma_file') && $application->diploma_path) {
            $this->workflow->registerUploadedDocument(
                $application,
                'diplome-ou-attestation',
                'Diplome ou attestation',
                $application->diploma_path
            );
        }

        if ($request->hasFile('photo_file') && $application->photo_path) {
            $this->workflow->registerUploadedDocument(
                $application,
                'photo-passeport',
                'Photo passeport',
                $application->photo_path
            );
        }
    }

    private function applicationResource(AdmissionApplication $application): array
    {
        return [
            'id' => $application->id,
            'application_number' => $application->application_number,
            'status' => $application->status,
            'academic_year' => $application->academic_year,
            'academic_year_id' => $application->academic_year_id,
            'level' => $application->level,
            'level_id' => $application->level_id,
            'promotion_id' => $application->promotion_id,
            'last_name' => $application->last_name,
            'post_name' => $application->post_name,
            'first_name' => $application->first_name,
            'gender' => $application->gender,
            'nationality' => $application->nationality,
            'email' => $application->email,
            'phone' => $application->phone,
            'address' => $application->address,
            'birth_date' => $application->birth_date?->toDateString(),
            'birth_place' => $application->birth_place,
            'last_school' => $application->last_school,
            'diploma_year' => $application->diploma_year,
            'percentage' => $application->percentage,
            'guardian_phone' => $application->guardian_phone,
            'comment' => $application->comment,
            'section' => $application->section,
            'program' => $application->program,
            'academic_year_record' => $application->academicYear,
            'academic_level' => $application->academicLevel,
            'promotion' => $application->promotion,
            'documents' => $application->applicationDocuments->map(fn ($document) => [
                'id' => $document->id,
                'name' => $document->name,
                'status' => $document->status,
                'type' => $document->documentType?->slug,
                'file_url' => $document->file_path ? asset('storage/'.$document->file_path) : null,
                'review_note' => $document->review_note,
            ])->values(),
            'student' => $application->student ? [
                'id' => $application->student->id,
                'matricule' => $application->student->matricule,
                'name' => $application->student->full_name,
            ] : null,
            'enrollment' => $application->student?->enrollments?->first() ? [
                'id' => $application->student->enrollments->first()->id,
                'enrollment_number' => $application->student->enrollments->first()->enrollment_number,
                'fiche_url' => $application->student->enrollments->first()->fiche_path
                    ? asset('storage/'.$application->student->enrollments->first()->fiche_path)
                    : null,
            ] : null,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'reviewed_at' => $application->reviewed_at?->toIso8601String(),
            'review_note' => $application->review_note,
            'student_message' => $application->student_message,
        ];
    }

    private function nextApplicationNumber(): string
    {
        $count = AdmissionApplication::whereDate('created_at', now()->toDateString())->count() + 1;

        return 'ISC-'.now()->format('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function academicYear(): string
    {
        $year = (int) now()->format('Y');

        return $year.'-'.($year + 1);
    }
}
