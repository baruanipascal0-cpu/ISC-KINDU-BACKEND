@extends('admin.layout')

@section('title', 'Paiements etudiants')
@section('subtitle', 'Creer, suivre et valider les frais visibles dans l espace etudiant')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Paiements</h2>
                <div class="admin-tabs">
                    <a href="{{ route('admin.student-payments.index') }}" @class(['active' => ! $status])>Tous</a>
                    @foreach ($statuses as $item)
                        <a href="{{ route('admin.student-payments.index', ['status' => $item]) }}" @class(['active' => $status === $item])>{{ $item }}</a>
                    @endforeach
                </div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.student-payments.create') }}">Nouveau paiement</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Etudiant</th>
                        <th>Reference</th>
                        <th>Montant</th>
                        <th>Etat</th>
                        <th>Preuve / recu</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        @php
                            $receipt = $payment->receipts->sortByDesc('issued_at')->first();
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $payment->user?->name ?? $payment->student?->full_name }}</strong>
                                <div class="muted">
                                    {{ $payment->student?->matricule ?? $payment->user?->studentProfile?->matricule ?? $payment->user?->email }}
                                </div>
                            </td>
                            <td>
                                <strong>{{ $payment->reference }}</strong>
                                <div class="muted">{{ $payment->label }}</div>
                            </td>
                            <td>
                                {{ number_format((float) $payment->paid_amount, 2, ',', ' ') }}
                                /
                                {{ number_format((float) $payment->amount, 2, ',', ' ') }}
                                {{ $payment->currency }}
                                <div class="muted">Echeance: {{ $payment->due_date?->format('d/m/Y') ?? 'Non definie' }}</div>
                            </td>
                            <td><span class="badge">{{ $payment->status }}</span></td>
                            <td>
                                <div class="actions">
                                    @if ($payment->proof_path)
                                        <a class="btn btn-muted" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->proof_path) }}" target="_blank">Preuve</a>
                                    @endif
                                    @if ($receipt?->file_path)
                                        <a class="btn btn-secondary" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($receipt->file_path) }}" target="_blank">Recu</a>
                                    @endif
                                    @if (! $payment->proof_path && ! $receipt?->file_path)
                                        <span class="muted">Aucun fichier</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.student-payments.edit', $payment) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.student-payments.destroy', $payment) }}" onsubmit="return confirm('Supprimer ce paiement ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Aucun paiement etudiant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body pagination">{{ $payments->links() }}</div>
    </section>
@endsection
