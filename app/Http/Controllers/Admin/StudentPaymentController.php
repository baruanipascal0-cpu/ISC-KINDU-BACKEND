<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentPaymentController extends Controller
{
    private const STATUSES = ['pending', 'submitted', 'confirmed', 'paid', 'rejected', 'cancelled'];

    public function index(Request $request): View
    {
        return view('admin.student-payments.index', [
            'payments' => Payment::query()
                ->with(['user.studentProfile', 'student', 'enrollment', 'admissionApplication', 'receipts'])
                ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $request->query('status'),
            'statuses' => self::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.student-payments.form', [
            'payment' => new Payment([
                'reference' => $this->nextReference(),
                'currency' => 'CDF',
                'status' => 'pending',
                'due_date' => now()->addMonth(),
            ]),
            'receipt' => new Receipt([
                'receipt_number' => $this->nextReceiptNumber(),
                'currency' => 'CDF',
                'issued_at' => now(),
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $payment = Payment::create($this->payload($request));
            $this->syncReceipt($request, $payment);
        });

        return redirect()
            ->route('admin.student-payments.index')
            ->with('status', 'Paiement etudiant cree.');
    }

    public function edit(Payment $payment): View
    {
        return view('admin.student-payments.form', [
            'payment' => $payment->load(['receipts']),
            'receipt' => $payment->receipts()->latest('issued_at')->latest()->first() ?? new Receipt([
                'receipt_number' => $this->nextReceiptNumber(),
                'currency' => $payment->currency,
                'amount' => $payment->paid_amount ?: $payment->amount,
                'issued_at' => now(),
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $oldProof = $payment->proof_path;

        DB::transaction(function () use ($request, $payment): void {
            $payment->update($this->payload($request, $payment));
            $this->syncReceipt($request, $payment);
        });

        if ($oldProof && $oldProof !== $payment->fresh()->proof_path) {
            Storage::disk('public')->delete($oldProof);
        }

        return redirect()
            ->route('admin.student-payments.index')
            ->with('status', 'Paiement etudiant mis a jour.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        if ($payment->proof_path) {
            Storage::disk('public')->delete($payment->proof_path);
        }

        $payment->receipts->each(function (Receipt $receipt): void {
            if ($receipt->file_path) {
                Storage::disk('public')->delete($receipt->file_path);
            }
        });

        $payment->delete();

        return back()->with('status', 'Paiement etudiant supprime.');
    }

    private function payload(Request $request, ?Payment $payment = null): array
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'admission_application_id' => ['nullable', 'integer', 'exists:admission_applications,id'],
            'enrollment_id' => ['nullable', 'integer', 'exists:enrollments,id'],
            'reference' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('payments', 'reference')->ignore($payment?->id),
            ],
            'label' => ['required', 'string', 'max:190'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'clear_proof' => ['nullable', 'boolean'],
        ]);

        $user = User::query()->with(['studentProfile', 'currentApplication'])->findOrFail($data['user_id']);
        $enrollment = ! empty($data['enrollment_id'])
            ? Enrollment::query()->with('student')->find($data['enrollment_id'])
            : $user->studentProfile?->enrollments()->latest()->first();
        $application = ! empty($data['admission_application_id'])
            ? AdmissionApplication::query()->find($data['admission_application_id'])
            : $user->currentApplication;
        $proofPath = $payment?->proof_path;

        if ($request->boolean('clear_proof')) {
            $proofPath = null;
        }

        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('payments/proofs', 'public');
        }

        $status = $data['status'];
        $paidAt = $data['paid_at'] ?? $payment?->paid_at;

        if (! $paidAt && in_array($status, ['confirmed', 'paid'], true)) {
            $paidAt = now();
        }

        return [
            'user_id' => $user->id,
            'student_id' => $enrollment?->student_id ?? $user->studentProfile?->id,
            'admission_application_id' => $application?->id,
            'enrollment_id' => $enrollment?->id,
            'reference' => trim((string) ($data['reference'] ?? '')) ?: $this->nextReference(),
            'label' => $data['label'],
            'amount' => $data['amount'],
            'paid_amount' => $data['paid_amount'] ?? 0,
            'currency' => Str::upper($data['currency']),
            'status' => $status,
            'due_date' => $data['due_date'] ?? null,
            'paid_at' => $paidAt,
            'proof_path' => $proofPath,
        ];
    }

    private function syncReceipt(Request $request, Payment $payment): void
    {
        $receipt = $payment->receipts()->latest('issued_at')->latest()->first();

        $data = $request->validate([
            'receipt_number' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('receipts', 'receipt_number')->ignore($receipt?->id),
            ],
            'receipt_amount' => ['nullable', 'numeric', 'min:0'],
            'receipt_issued_at' => ['nullable', 'date'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'clear_receipt_file' => ['nullable', 'boolean'],
        ]);

        $hasReceiptInput = $request->filled('receipt_number')
            || $request->filled('receipt_amount')
            || $request->filled('receipt_issued_at')
            || $request->hasFile('receipt_file');

        if (! $hasReceiptInput) {
            return;
        }

        $filePath = $receipt?->file_path;

        if ($request->boolean('clear_receipt_file')) {
            $filePath = null;
        }

        if ($request->hasFile('receipt_file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }

            $filePath = $request->file('receipt_file')->store('payments/receipts', 'public');
        }

        $payload = [
            'user_id' => $payment->user_id,
            'student_id' => $payment->student_id,
            'enrollment_id' => $payment->enrollment_id,
            'receipt_number' => trim((string) ($data['receipt_number'] ?? '')) ?: $this->nextReceiptNumber(),
            'amount' => $data['receipt_amount'] ?? ($payment->paid_amount ?: $payment->amount),
            'currency' => $payment->currency,
            'issued_at' => $data['receipt_issued_at'] ?? now(),
            'file_path' => $filePath,
        ];

        if ($receipt) {
            $receipt->update($payload);

            return;
        }

        $payment->receipts()->create($payload);
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
            'enrollments' => Enrollment::query()
                ->with(['student', 'academicYear', 'program', 'promotion'])
                ->latest()
                ->take(200)
                ->get(),
            'statuses' => self::STATUSES,
        ];
    }

    private function nextReference(): string
    {
        return 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }

    private function nextReceiptNumber(): string
    {
        return 'REC-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }
}
