@extends('admin.layout')

@section('title', $payment->exists ? 'Modifier un paiement' : 'Nouveau paiement')
@section('subtitle', 'Alimente les frais et recus affiches dans l espace etudiant')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $payment->exists ? route('admin.student-payments.update', $payment) : route('admin.student-payments.store') }}">
        @csrf
        @if ($payment->exists)
            @method('PUT')
        @endif

        <div class="card-header">
            <h2 class="card-title">Informations du paiement</h2>
        </div>
        <div class="card-body form-grid">
            <label class="field full">
                <span>Etudiant</span>
                <select name="user_id" required>
                    <option value="">Choisir un etudiant</option>
                    @foreach ($studentUsers as $user)
                        <option value="{{ $user->id }}" @selected((int) old('user_id', $payment->user_id) === $user->id)>
                            {{ $user->name }} - {{ $user->studentProfile?->matricule ?? $user->email }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Dossier inscription lie</span>
                <select name="admission_application_id">
                    <option value="">Automatique</option>
                    @foreach ($applications as $application)
                        <option value="{{ $application->id }}" @selected((int) old('admission_application_id', $payment->admission_application_id) === $application->id)>
                            {{ $application->application_number }} - {{ $application->user?->name ?? $application->full_name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Inscription officielle liee</span>
                <select name="enrollment_id">
                    <option value="">Automatique</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}" @selected((int) old('enrollment_id', $payment->enrollment_id) === $enrollment->id)>
                            {{ $enrollment->enrollment_number }} - {{ $enrollment->student?->full_name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Reference</span>
                <input name="reference" value="{{ old('reference', $payment->reference) }}">
            </label>

            <label class="field">
                <span>Libelle</span>
                <input name="label" value="{{ old('label', $payment->label) }}" required placeholder="Ex : Frais academiques">
            </label>

            <label class="field">
                <span>Montant attendu</span>
                <input name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $payment->amount ?? 0) }}" required>
            </label>

            <label class="field">
                <span>Montant paye</span>
                <input name="paid_amount" type="number" step="0.01" min="0" value="{{ old('paid_amount', $payment->paid_amount ?? 0) }}">
            </label>

            <label class="field">
                <span>Devise</span>
                <input name="currency" value="{{ old('currency', $payment->currency ?? 'CDF') }}" required>
            </label>

            <label class="field">
                <span>Statut</span>
                <select name="status" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $payment->status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Date limite</span>
                <input name="due_date" type="date" value="{{ old('due_date', optional($payment->due_date)->format('Y-m-d')) }}">
            </label>

            <label class="field">
                <span>Date de paiement</span>
                <input name="paid_at" type="datetime-local" value="{{ old('paid_at', optional($payment->paid_at)->format('Y-m-d\TH:i')) }}">
            </label>

            <label class="field">
                <span>Preuve de paiement</span>
                <input name="proof_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp">
            </label>

            @if ($payment->proof_path)
                <label class="checkbox-row">
                    <input type="checkbox" name="clear_proof" value="1">
                    Retirer la preuve actuelle
                </label>
            @endif
        </div>

        <div class="card-header">
            <h2 class="card-title">Recu a publier</h2>
        </div>
        <div class="card-body form-grid">
            <label class="field">
                <span>Numero recu</span>
                <input name="receipt_number" value="{{ old('receipt_number', $receipt->receipt_number) }}">
            </label>

            <label class="field">
                <span>Montant du recu</span>
                <input name="receipt_amount" type="number" step="0.01" min="0" value="{{ old('receipt_amount', $receipt->amount) }}">
            </label>

            <label class="field">
                <span>Date du recu</span>
                <input name="receipt_issued_at" type="datetime-local" value="{{ old('receipt_issued_at', optional($receipt->issued_at)->format('Y-m-d\TH:i')) }}">
            </label>

            <label class="field">
                <span>Fichier du recu</span>
                <input name="receipt_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp">
            </label>

            @if ($receipt->file_path)
                <label class="checkbox-row">
                    <input type="checkbox" name="clear_receipt_file" value="1">
                    Retirer le fichier recu actuel
                </label>
            @endif
        </div>

        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.student-payments.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
