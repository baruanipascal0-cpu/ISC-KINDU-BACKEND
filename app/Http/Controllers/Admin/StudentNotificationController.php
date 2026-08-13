<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\InstitutionNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentNotificationController extends Controller
{
    private const TYPES = ['info', 'admission', 'payment', 'document', 'success', 'warning', 'urgent'];

    public function index(Request $request): View
    {
        return view('admin.student-notifications.index', [
            'notifications' => InstitutionNotification::query()
                ->with(['user.studentProfile', 'admissionApplication'])
                ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'type' => $request->query('type'),
            'types' => self::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('admin.student-notifications.form', [
            'notification' => new InstitutionNotification([
                'type' => 'info',
                'channel' => 'database',
            ]),
            'isBroadcast' => false,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->payload($request);

        if ($request->boolean('send_to_all') && empty($data['user_id'])) {
            $count = 0;

            User::query()
                ->where('role', 'student')
                ->where('status', 'active')
                ->chunkById(100, function ($users) use ($data, &$count): void {
                    foreach ($users as $user) {
                        InstitutionNotification::create($data + ['user_id' => $user->id]);
                        $count += 1;
                    }
                });

            return redirect()
                ->route('admin.student-notifications.index')
                ->with('status', $count.' notification(s) envoyee(s).');
        }

        InstitutionNotification::create($data);

        return redirect()
            ->route('admin.student-notifications.index')
            ->with('status', 'Notification envoyee.');
    }

    public function edit(InstitutionNotification $notification): View
    {
        return view('admin.student-notifications.form', [
            'notification' => $notification,
            'isBroadcast' => false,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, InstitutionNotification $notification): RedirectResponse
    {
        $notification->update($this->payload($request));

        return redirect()
            ->route('admin.student-notifications.index')
            ->with('status', 'Notification mise a jour.');
    }

    public function destroy(InstitutionNotification $notification): RedirectResponse
    {
        $notification->delete();

        return back()->with('status', 'Notification supprimee.');
    }

    private function payload(Request $request): array
    {
        $data = $request->validate([
            'user_id' => [Rule::requiredIf(! $request->boolean('send_to_all')), 'nullable', 'integer', 'exists:users,id'],
            'admission_application_id' => ['nullable', 'integer', 'exists:admission_applications,id'],
            'send_to_all' => ['nullable', 'boolean'],
            'type' => ['required', Rule::in(self::TYPES)],
            'title' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        return [
            'user_id' => $data['user_id'] ?? null,
            'admission_application_id' => $data['admission_application_id'] ?? null,
            'type' => $data['type'],
            'channel' => 'database',
            'title' => $data['title'],
            'message' => $data['message'],
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
            'types' => self::TYPES,
        ];
    }
}
