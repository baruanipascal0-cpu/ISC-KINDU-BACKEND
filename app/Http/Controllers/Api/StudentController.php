<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\InstitutionNotification;
use App\Models\StudentComment;
use App\Models\StudentDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends ApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $application = $user->currentApplication()
            ->with(['section', 'program', 'academicYear', 'academicLevel', 'promotion', 'student.enrollments'])
            ->first();
        $student = $user->studentProfile()
            ->with(['section', 'program', 'enrollments.academicYear', 'enrollments.section', 'enrollments.program', 'enrollments.level', 'enrollments.promotion'])
            ->first();
        $enrollment = $student?->enrollments?->first();
        $registrationSheet = $user->documents()
            ->where('type', 'fiche-inscription')
            ->latest('issued_at')
            ->latest()
            ->first();

        return $this->ok([
            'student' => [
                'id' => $student?->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'matricule' => $student?->matricule,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'application' => $application,
            'enrollment' => $this->enrollmentResource($enrollment),
            'registration_sheet' => $this->registrationSheetResource($registrationSheet, $enrollment),
            'summary' => [
                'payments_pending' => $user->payments()->where('status', 'pending')->count(),
                'documents_available' => $user->documents()->where('status', 'available')->count(),
                'comments_open' => $user->comments()->where('status', 'open')->count(),
            ],
            'payments' => $user->payments()->latest()->take(5)->get(),
            'documents' => $user->documents()->latest()->take(12)->get()->map(fn (StudentDocument $document) => $this->documentResource($document)),
            'comments' => $user->comments()->latest()->take(5)->get(),
            'notifications' => $user->institutionNotifications()->latest()->take(5)->get()->map(fn (InstitutionNotification $notification) => $this->notificationResource($notification)),
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        return $this->ok(
            $request->user()
                ->payments()
                ->latest()
                ->get()
        );
    }

    public function storePaymentProof(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'proof_file' => ['nullable', 'file', 'max:5120'],
            'reference_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = Payment::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($data['payment_id']);

        if ($request->hasFile('proof_file')) {
            $payment->proof_path = $request->file('proof_file')->store('payments/proofs', 'public');
        }

        $payment->status = 'submitted';
        $payment->save();

        return $this->ok($payment, 'Preuve de paiement envoyee.');
    }

    public function documents(Request $request): JsonResponse
    {
        return $this->ok(
            $request->user()
                ->documents()
                ->latest()
                ->get()
                ->map(fn (StudentDocument $document) => $this->documentResource($document))
        );
    }

    public function storeDocument(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'type' => ['nullable', 'string', 'max:80'],
            'file' => ['nullable', 'file', 'max:5120'],
        ]);

        $document = StudentDocument::create([
            'user_id' => $request->user()->id,
            'admission_application_id' => $request->user()->currentApplication?->id,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'document',
            'file_path' => $request->hasFile('file') ? $request->file('file')->store('student/documents', 'public') : null,
            'status' => 'submitted',
            'issued_at' => now(),
        ]);

        return $this->ok($document, 'Document envoye.', 201);
    }

    public function comments(Request $request): JsonResponse
    {
        return $this->ok(
            $request->user()
                ->comments()
                ->latest()
                ->get()
        );
    }

    public function storeComment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $comment = StudentComment::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'open',
        ]);

        return $this->ok($comment, 'Commentaire envoye.', 201);
    }

    public function notifications(Request $request): JsonResponse
    {
        return $this->ok(
            $request->user()
                ->institutionNotifications()
                ->latest()
                ->get()
                ->map(fn (InstitutionNotification $notification) => $this->notificationResource($notification))
        );
    }

    private function enrollmentResource(?Enrollment $enrollment): ?array
    {
        if (! $enrollment) {
            return null;
        }

        return [
            'id' => $enrollment->id,
            'enrollment_number' => $enrollment->enrollment_number,
            'type' => $enrollment->type,
            'status' => $enrollment->status,
            'enrolled_on' => $enrollment->enrolled_on?->toDateString(),
            'academic_year' => $enrollment->academicYear,
            'section' => $enrollment->section,
            'program' => $enrollment->program,
            'level' => $enrollment->level,
            'promotion' => $enrollment->promotion,
            'fiche_path' => $enrollment->fiche_path,
            'fiche_url' => $enrollment->fiche_path ? asset('storage/'.$enrollment->fiche_path) : null,
        ];
    }

    private function registrationSheetResource(?StudentDocument $document, ?Enrollment $enrollment): ?array
    {
        if ($document) {
            return $this->documentResource($document);
        }

        if (! $enrollment?->fiche_path) {
            return null;
        }

        return [
            'id' => null,
            'name' => 'Fiche d inscription',
            'type' => 'fiche-inscription',
            'status' => $enrollment->status ?: 'available',
            'issued_at' => $enrollment->enrolled_on?->toIso8601String(),
            'file_path' => $enrollment->fiche_path,
            'file_url' => asset('storage/'.$enrollment->fiche_path),
        ];
    }

    private function documentResource(StudentDocument $document): array
    {
        return [
            'id' => $document->id,
            'name' => $document->name,
            'type' => $document->type,
            'status' => $document->status,
            'issued_at' => $document->issued_at?->toIso8601String(),
            'file_path' => $document->file_path,
            'file_url' => $document->file_path ? asset('storage/'.$document->file_path) : null,
        ];
    }

    private function notificationResource(InstitutionNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
