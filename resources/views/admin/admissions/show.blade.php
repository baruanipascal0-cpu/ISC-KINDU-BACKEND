@extends('admin.layout')

@section('title', 'Dossier d admission')
@section('subtitle', $application->application_number)

@section('content')
    <div class="actions" style="margin-bottom: 18px;">
        <a class="btn btn-muted" href="{{ route('admin.admissions.index') }}">Retour</a>
        @if ($application->student?->enrollments?->first())
            <a class="btn btn-secondary" href="{{ route('admin.registry.show', $application->student->enrollments->first()) }}">Fiche registre</a>
        @endif
    </div>

    <div class="grid" style="grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); align-items: start;">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Identite complete</h2>
                <span class="badge">{{ $application->status }}</span>
            </div>
            <div class="card-body">
                <dl class="check-list">
                    <div><dt>Nom complet</dt><dd>{{ $application->full_name }}</dd></div>
                    <div><dt>Sexe</dt><dd>{{ $application->gender ?? 'Non renseigne' }}</dd></div>
                    <div><dt>Nationalite</dt><dd>{{ $application->nationality ?? 'Non renseignee' }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $application->email }}</dd></div>
                    <div><dt>Telephone</dt><dd>{{ $application->phone ?? 'Non renseigne' }}</dd></div>
                    <div><dt>Adresse</dt><dd>{{ $application->address ?? 'Non renseignee' }}</dd></div>
                    <div><dt>Naissance</dt><dd>{{ $application->birth_place ?? 'Non renseigne' }} {{ $application->birth_date?->format('d/m/Y') }}</dd></div>
                    <div><dt>Ecole de provenance</dt><dd>{{ $application->last_school ?? 'Non renseignee' }}</dd></div>
                    <div><dt>Diplome</dt><dd>{{ $application->diploma_year ?? 'Non renseigne' }} / {{ $application->percentage ? $application->percentage.'%' : 'Pourcentage non renseigne' }}</dd></div>
                    <div><dt>Tuteur</dt><dd>{{ $application->guardian_phone ?? 'Non renseigne' }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Choix academique</h2>
            </div>
            <div class="card-body">
                <dl class="check-list">
                    <div><dt>Annee</dt><dd>{{ $application->academicYear?->code ?? $application->academic_year }}</dd></div>
                    <div><dt>Section</dt><dd>{{ $application->section?->name ?? 'Non definie' }}</dd></div>
                    <div><dt>Filiere</dt><dd>{{ $application->program?->name ?? 'Non definie' }}</dd></div>
                    <div><dt>Niveau</dt><dd>{{ $application->academicLevel?->name ?? $application->level }}</dd></div>
                    <div><dt>Promotion</dt><dd>{{ $application->promotion?->name ?? 'Non definie' }}</dd></div>
                    <div><dt>Soumis le</dt><dd>{{ $application->submitted_at?->format('d/m/Y H:i') ?? 'Non soumis' }}</dd></div>
                    <div><dt>Controle le</dt><dd>{{ $application->reviewed_at?->format('d/m/Y H:i') ?? 'Non controle' }}</dd></div>
                </dl>
            </div>
        </section>
    </div>

    <div class="grid" style="grid-template-columns: minmax(0, 1fr) minmax(320px, .8fr); align-items: start; margin-top: 24px;">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Pieces du dossier</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Piece</th>
                            <th>Fichier</th>
                            <th>Statut</th>
                            <th>Validation</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($application->applicationDocuments as $document)
                        <tr>
                            <td>
                                <strong>{{ $document->name }}</strong>
                                <div class="muted">{{ $document->documentType?->name }}</div>
                            </td>
                            <td>
                                @if ($document->file_path)
                                    <a class="btn btn-muted" href="{{ asset('storage/'.$document->file_path) }}" target="_blank">Ouvrir</a>
                                @else
                                    <span class="muted">Aucun fichier</span>
                                @endif
                            </td>
                            <td><span class="badge">{{ $document->status }}</span></td>
                            <td>
                                <form method="post" action="{{ route('admin.admissions.documents.update', [$application, $document]) }}" class="grid" style="min-width: 260px;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status">
                                        @foreach ($documentStatuses as $item)
                                            <option value="{{ $item }}" @selected($document->status === $item)>{{ $item }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="internal_note" placeholder="Note interne">{{ $document->review_note }}</textarea>
                                    <textarea name="student_message" placeholder="Message visible par l etudiant"></textarea>
                                    <button class="btn btn-secondary" type="submit">Valider la piece</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Aucune piece jointe pour ce dossier.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Decision administrative</h2>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.admissions.status', $application) }}" class="grid">
                    @csrf
                    @method('PATCH')
                    <label class="field">
                        <span>Statut</span>
                        <select name="status">
                            @foreach ($statuses as $item)
                                <option value="{{ $item }}" @selected($application->status === $item)>{{ $item }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>Note interne</span>
                        <textarea name="internal_note" placeholder="Visible seulement par l administration">{{ $application->internal_note }}</textarea>
                    </label>
                    <label class="field">
                        <span>Message etudiant</span>
                        <textarea name="student_message" placeholder="Visible dans le portefeuille etudiant">{{ $application->student_message }}</textarea>
                    </label>
                    <button class="btn btn-primary" type="submit">Enregistrer la decision</button>
                </form>
            </div>
        </section>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); margin-top: 24px;">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Historique</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Decision</th><th>Admin</th><th>Notes</th></tr></thead>
                    <tbody>
                    @forelse ($application->decisions->sortByDesc('decided_at') as $decision)
                        <tr>
                            <td>{{ $decision->decided_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $decision->from_status }} -> {{ $decision->to_status }}</td>
                            <td>{{ $decision->user?->email ?? 'Systeme' }}</td>
                            <td>
                                <div>{{ $decision->internal_note }}</div>
                                <div class="muted">{{ $decision->student_message }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Aucune decision.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Commentaires</h2>
            </div>
            <div class="card-body">
                <dl class="check-list">
                    <div><dt>Candidat</dt><dd>{{ $application->comment ?? 'Aucun commentaire' }}</dd></div>
                    <div><dt>Administration</dt><dd>{{ $application->internal_note ?? 'Aucune note interne' }}</dd></div>
                    <div><dt>Message etudiant</dt><dd>{{ $application->student_message ?? 'Aucun message' }}</dd></div>
                </dl>
            </div>
        </section>
    </div>
@endsection
