<?php

namespace App\Http\Controllers\Api;

use App\Models\AdmissionApplication;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Publication;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\AdmissionWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends ApiController
{
    public function __construct(private readonly AdmissionWorkflowService $workflow)
    {
    }

    public function overview(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return $this->ok([
            'users' => User::count(),
            'students' => Student::count(),
            'enrollments' => Enrollment::count(),
            'admissions' => AdmissionApplication::count(),
            'admissions_pending' => AdmissionApplication::whereIn('status', ['draft', 'submitted', 'under_review', 'needs_correction'])->count(),
            'sections' => Section::count(),
            'news' => NewsPost::count(),
            'publications' => Publication::count(),
            'events' => Event::count(),
            'messages' => ContactMessage::where('status', 'new')->count(),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $users = User::query()
            ->when($request->query('role'), fn ($query, string $role) => $query->where('role', $role))
            ->latest()
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return $this->ok($users->items(), meta: [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
        ]);
    }

    public function admissions(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $applications = AdmissionApplication::query()
            ->with(['user', 'section', 'program', 'academicYear', 'academicLevel', 'promotion'])
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return $this->ok($applications->items(), meta: [
            'current_page' => $applications->currentPage(),
            'last_page' => $applications->lastPage(),
            'total' => $applications->total(),
        ]);
    }

    public function updateAdmissionStatus(Request $request, AdmissionApplication $application): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'status' => ['required', Rule::in(AdmissionWorkflowService::STATUSES)],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'student_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $application = $this->workflow->transition(
            $application,
            $data['status'],
            $request->user(),
            $data['internal_note'] ?? null,
            $data['student_message'] ?? null,
            $request
        );

        return $this->ok($application, 'Etat du dossier mis a jour.');
    }

    public function content(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return $this->ok([
            'pages' => Page::latest()->get(),
            'news' => NewsPost::latest()->get(),
            'publications' => Publication::latest()->get(),
            'events' => Event::latest()->get(),
            'sections' => Section::with('programs')->orderBy('sort_order')->get(),
        ]);
    }

    public function audit(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return $this->ok($logs->items(), meta: [
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Acces reserve a l administration.');
    }

}
