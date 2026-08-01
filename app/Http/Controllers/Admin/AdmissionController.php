<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\ApplicationDocument;
use App\Services\AdmissionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function __construct(private readonly AdmissionWorkflowService $workflow)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.admissions.index', [
            'applications' => AdmissionApplication::with([
                'user',
                'section',
                'program',
                'academicYear',
                'academicLevel',
                'promotion',
            ])
                ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20),
            'status' => $request->query('status'),
            'statuses' => AdmissionWorkflowService::STATUSES,
        ]);
    }

    public function show(AdmissionApplication $application): View
    {
        return view('admin.admissions.show', [
            'application' => $application->load([
                'user',
                'section',
                'program',
                'academicYear',
                'academicLevel',
                'promotion',
                'reviewer',
                'applicationDocuments.documentType',
                'applicationDocuments.reviews.user',
                'decisions.user',
                'student.enrollments.academicYear',
                'student.enrollments.section',
                'student.enrollments.program',
                'student.enrollments.level',
                'student.enrollments.promotion',
                'payments',
            ]),
            'statuses' => AdmissionWorkflowService::STATUSES,
            'documentStatuses' => AdmissionWorkflowService::DOCUMENT_STATUSES,
        ]);
    }

    public function updateStatus(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(AdmissionWorkflowService::STATUSES)],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'student_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workflow->transition(
            $application,
            $data['status'],
            $request->user(),
            $data['internal_note'] ?? null,
            $data['student_message'] ?? null,
            $request
        );

        return back()->with('status', 'Dossier mis a jour.');
    }

    public function updateDocument(Request $request, AdmissionApplication $application, ApplicationDocument $document): RedirectResponse
    {
        abort_unless($document->admission_application_id === $application->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(AdmissionWorkflowService::DOCUMENT_STATUSES)],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'student_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workflow->reviewDocument(
            $document,
            $data['status'],
            $request->user(),
            $data['internal_note'] ?? null,
            $data['student_message'] ?? null,
            $request
        );

        return back()->with('status', 'Piece mise a jour.');
    }
}
