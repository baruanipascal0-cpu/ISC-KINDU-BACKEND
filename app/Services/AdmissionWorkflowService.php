<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\AdmissionDecision;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\DocumentReview;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\InstitutionNotification;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdmissionWorkflowService
{
    public const STATUSES = [
        'draft',
        'submitted',
        'under_review',
        'needs_correction',
        'approved',
        'rejected',
        'cancelled',
    ];

    public const DOCUMENT_STATUSES = [
        'submitted',
        'accepted',
        'needs_correction',
        'rejected',
    ];

    public function transition(
        AdmissionApplication $application,
        string $status,
        ?User $actor = null,
        ?string $internalNote = null,
        ?string $studentMessage = null,
        ?Request $request = null
    ): AdmissionApplication {
        return DB::transaction(function () use ($application, $status, $actor, $internalNote, $studentMessage, $request) {
            $application->refresh();
            $fromStatus = $application->status;
            $now = now();

            $payload = [
                'status' => $status,
                'reviewed_at' => $now,
                'reviewed_by' => $actor?->id,
            ];

            if ($internalNote !== null) {
                $payload['internal_note'] = $internalNote;
                $payload['review_note'] = $internalNote;
            }

            if ($studentMessage !== null) {
                $payload['student_message'] = $studentMessage;
            }

            if ($status === 'approved') {
                $payload['approved_at'] = $now;
                $payload['rejected_at'] = null;
                $payload['cancelled_at'] = null;
            }

            if ($status === 'rejected') {
                $payload['rejected_at'] = $now;
                $payload['approved_at'] = null;
            }

            if ($status === 'cancelled') {
                $payload['cancelled_at'] = $now;
            }

            $application->update($payload);

            $this->recordDecision($application, $fromStatus, $status, $actor, $internalNote, $studentMessage);

            if ($studentMessage !== null && $studentMessage !== '') {
                $this->notifyStudent(
                    $application,
                    'admission',
                    'Message de l administration',
                    $studentMessage
                );
            }

            if ($status === 'approved') {
                $enrollment = $this->approve($application, $actor);

                $this->notifyStudent(
                    $application,
                    'admission_approved',
                    'Admission approuvee',
                    'Votre dossier est approuve. Votre numero d inscription est '.$enrollment->enrollment_number.'. Votre fiche d inscription est disponible dans vos documents.'
                );
            }

            $this->audit($actor, 'admission.status_changed', $application, [
                'from_status' => $fromStatus,
                'to_status' => $status,
            ], $request);

            return $application->fresh([
                'user',
                'section',
                'program',
                'academicYear',
                'academicLevel',
                'promotion',
                'applicationDocuments.documentType',
                'decisions.user',
                'student.enrollments',
            ]);
        });
    }

    public function reviewDocument(
        ApplicationDocument $document,
        string $status,
        ?User $actor = null,
        ?string $internalNote = null,
        ?string $studentMessage = null,
        ?Request $request = null
    ): ApplicationDocument {
        return DB::transaction(function () use ($document, $status, $actor, $internalNote, $studentMessage, $request) {
            $document->update([
                'status' => $status,
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
                'review_note' => $internalNote,
            ]);

            DocumentReview::create([
                'application_document_id' => $document->id,
                'user_id' => $actor?->id,
                'decision' => $status,
                'internal_note' => $internalNote,
                'student_message' => $studentMessage,
                'decided_at' => now(),
            ]);

            if ($studentMessage !== null && $studentMessage !== '') {
                $application = $document->admissionApplication;

                $this->notifyStudent(
                    $application,
                    'document',
                    'Controle de document',
                    $studentMessage
                );
            }

            $this->audit($actor, 'admission.document_reviewed', $document, [
                'status' => $status,
            ], $request);

            return $document->fresh(['documentType', 'reviews.user']);
        });
    }

    public function registerUploadedDocument(
        AdmissionApplication $application,
        string $typeSlug,
        string $name,
        string $path
    ): ApplicationDocument {
        $type = DocumentType::query()->firstOrCreate([
            'slug' => $typeSlug,
        ], [
            'name' => $name,
            'is_required' => true,
            'is_active' => true,
        ]);

        return ApplicationDocument::updateOrCreate([
            'admission_application_id' => $application->id,
            'document_type_id' => $type->id,
        ], [
            'name' => $name,
            'file_path' => $path,
            'status' => 'submitted',
            'uploaded_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ]);
    }

    public function approve(AdmissionApplication $application, ?User $actor = null): Enrollment
    {
        $application->loadMissing(['section', 'program', 'academicYear', 'academicLevel', 'promotion', 'user']);

        $academicYear = $this->resolveAcademicYear($application);
        $level = $this->resolveLevel($application);
        $promotion = $this->resolvePromotion($application, $level);

        $student = Student::query()
            ->where('admission_application_id', $application->id)
            ->orWhere('user_id', $application->user_id)
            ->first();

        $studentPayload = [
            'user_id' => $application->user_id,
            'admission_application_id' => $application->id,
            'section_id' => $application->section_id,
            'program_id' => $application->program_id,
            'last_name' => $application->last_name,
            'post_name' => $application->post_name,
            'first_name' => $application->first_name,
            'gender' => $application->gender,
            'birth_date' => $application->birth_date,
            'birth_place' => $application->birth_place,
            'email' => $application->email,
            'phone' => $application->phone,
            'address' => $application->address,
            'status' => 'active',
            'admitted_at' => $application->approved_at ?? now(),
        ];

        if ($student) {
            $student->update($studentPayload);
        } else {
            $student = Student::create($studentPayload + [
                'matricule' => $this->nextMatricule($academicYear),
            ]);
        }

        $application->user?->update([
            'name' => trim($application->first_name.' '.$application->last_name),
            'first_name' => $application->first_name,
            'last_name' => $application->last_name,
            'phone' => $application->phone,
            'role' => 'student',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::query()
            ->where('admission_application_id', $application->id)
            ->first();

        $enrollmentPayload = [
            'student_id' => $student->id,
            'admission_application_id' => $application->id,
            'academic_year_id' => $academicYear->id,
            'section_id' => $application->section_id,
            'program_id' => $application->program_id,
            'level_id' => $level?->id,
            'promotion_id' => $promotion?->id,
            'type' => 'nouvelle_inscription',
            'status' => 'active',
            'enrolled_on' => now()->toDateString(),
            'metadata' => [
                'source' => 'admission_approval',
                'approved_by' => $actor?->id,
            ],
        ];

        if ($enrollment) {
            $enrollment->update($enrollmentPayload);
        } else {
            $enrollment = Enrollment::create($enrollmentPayload + [
                'enrollment_number' => $this->nextEnrollmentNumber($academicYear),
            ]);
        }

        $fichePath = $this->generateEnrollmentSheet($application, $student, $enrollment, $academicYear, $level, $promotion);
        $enrollment->update(['fiche_path' => $fichePath]);

        Payment::updateOrCreate([
            'reference' => 'PAY-'.$application->application_number.'-INS',
        ], [
            'user_id' => $application->user_id,
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'admission_application_id' => $application->id,
            'label' => 'Frais d inscription',
            'amount' => 0,
            'paid_amount' => 0,
            'currency' => 'CDF',
            'status' => 'pending',
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        StudentDocument::updateOrCreate([
            'user_id' => $application->user_id,
            'admission_application_id' => $application->id,
            'type' => 'fiche-inscription',
        ], [
            'name' => 'Fiche d inscription',
            'file_path' => $fichePath,
            'status' => 'available',
            'issued_at' => now(),
        ]);

        return $enrollment->fresh(['student', 'academicYear', 'section', 'program', 'level', 'promotion']);
    }

    private function recordDecision(
        AdmissionApplication $application,
        ?string $fromStatus,
        string $toStatus,
        ?User $actor,
        ?string $internalNote,
        ?string $studentMessage
    ): void {
        AdmissionDecision::create([
            'admission_application_id' => $application->id,
            'user_id' => $actor?->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'internal_note' => $internalNote,
            'student_message' => $studentMessage,
            'decided_at' => now(),
        ]);
    }

    private function notifyStudent(AdmissionApplication $application, string $type, string $title, string $message): void
    {
        InstitutionNotification::create([
            'user_id' => $application->user_id,
            'admission_application_id' => $application->id,
            'type' => $type,
            'channel' => 'database',
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function audit(?User $actor, string $action, object $model, array $afterValues, ?Request $request): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->id ?? null,
            'description' => $action,
            'after_values' => $afterValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    private function resolveAcademicYear(AdmissionApplication $application): AcademicYear
    {
        if ($application->academicYear) {
            return $application->academicYear;
        }

        $code = $application->academic_year ?: $this->defaultAcademicYear();

        return AcademicYear::query()->firstOrCreate([
            'code' => $code,
        ], [
            'name' => $code,
            'status' => 'active',
            'is_current' => ! AcademicYear::where('is_current', true)->exists(),
        ]);
    }

    private function resolveLevel(AdmissionApplication $application): ?Level
    {
        if ($application->academicLevel) {
            return $application->academicLevel;
        }

        if (! $application->level) {
            return null;
        }

        return Level::query()
            ->where('code', $application->level)
            ->orWhere('name', $application->level)
            ->first();
    }

    private function resolvePromotion(AdmissionApplication $application, ?Level $level): ?Promotion
    {
        if ($application->promotion) {
            return $application->promotion;
        }

        if (! $level) {
            return null;
        }

        return Promotion::query()
            ->where('level_id', $level->id)
            ->where(function ($query) use ($level) {
                $query->where('code', $level->code)
                    ->orWhere('name', $level->name);
            })
            ->first();
    }

    private function nextMatricule(AcademicYear $academicYear): string
    {
        $prefix = 'ISC-'.substr(preg_replace('/\D/', '', $academicYear->code) ?: (string) now()->year, 0, 4);
        $count = Student::where('matricule', 'like', $prefix.'-%')->count() + 1;

        return $prefix.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function nextEnrollmentNumber(AcademicYear $academicYear): string
    {
        $yearCode = preg_replace('/[^A-Za-z0-9]/', '', $academicYear->code) ?: now()->format('Y');
        $count = Enrollment::where('academic_year_id', $academicYear->id)->count() + 1;

        return 'INS-'.$yearCode.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function generateEnrollmentSheet(
        AdmissionApplication $application,
        Student $student,
        Enrollment $enrollment,
        AcademicYear $academicYear,
        ?Level $level,
        ?Promotion $promotion
    ): string {
        $html = view('admin.registry.fiche', [
            'application' => $application,
            'student' => $student,
            'enrollment' => $enrollment,
            'academicYear' => $academicYear,
            'level' => $level,
            'promotion' => $promotion,
        ])->render();

        $path = 'enrollments/fiche-'.$enrollment->enrollment_number.'.html';

        Storage::disk('public')->put($path, $html);

        return $path;
    }

    private function defaultAcademicYear(): string
    {
        $year = (int) now()->format('Y');

        return $year.'-'.($year + 1);
    }
}
