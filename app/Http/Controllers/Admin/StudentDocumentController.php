<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentDocumentController extends Controller
{
    private const STATUSES = ['available', 'submitted', 'reviewed', 'needs_correction', 'archived'];

    private const TYPES = [
        'fiche-inscription',
        'attestation',
        'releve',
        'recu',
        'bulletin',
        'document',
        'autre',
    ];

    public function index(Request $request): View
    {
        return view('admin.student-documents.index', [
            'documents' => StudentDocument::query()
                ->with(['user.studentProfile', 'admissionApplication'])
                ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
                ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('admin.student-documents.form', [
            'document' => new StudentDocument([
                'type' => 'document',
                'status' => 'available',
                'issued_at' => now(),
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        StudentDocument::create($this->payload($request));

        return redirect()
            ->route('admin.student-documents.index')
            ->with('status', 'Document etudiant publie.');
    }

    public function edit(StudentDocument $document): View
    {
        return view('admin.student-documents.form', [
            'document' => $document,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, StudentDocument $document): RedirectResponse
    {
        $oldFile = $document->file_path;
        $document->update($this->payload($request, $document));

        if ($oldFile && $oldFile !== $document->file_path) {
            Storage::disk('public')->delete($oldFile);
        }

        return redirect()
            ->route('admin.student-documents.index')
            ->with('status', 'Document etudiant mis a jour.');
    }

    public function destroy(StudentDocument $document): RedirectResponse
    {
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('status', 'Document etudiant supprime.');
    }

    private function payload(Request $request, ?StudentDocument $document = null): array
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'admission_application_id' => ['nullable', 'integer', 'exists:admission_applications,id'],
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', 'string', 'max:80'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'issued_at' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,webp,txt', 'max:10240'],
            'clear_file' => ['nullable', 'boolean'],
        ]);

        $user = User::query()->with('currentApplication')->findOrFail($data['user_id']);
        $filePath = $document?->file_path;

        if ($request->boolean('clear_file')) {
            $filePath = null;
        }

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('student/documents', 'public');
        }

        return [
            'user_id' => $user->id,
            'admission_application_id' => $data['admission_application_id'] ?? $user->currentApplication?->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'],
            'issued_at' => $data['issued_at'] ?? now(),
            'file_path' => $filePath,
        ];
    }

    private function formOptions(): array
    {
        return [
            'studentUsers' => User::query()
                ->where('role', 'student')
                ->with(['studentProfile', 'currentApplication'])
                ->orderBy('name')
                ->get(),
            'applications' => AdmissionApplication::query()
                ->with('user')
                ->latest()
                ->take(200)
                ->get(),
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
        ];
    }
}
