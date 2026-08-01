@extends('admin.layout')

@section('title', 'Liste de diplomes')
@section('subtitle', $graduationList->title)

@section('content')
    <section class="registry-official-head">
        <img src="{{ asset('images/site/logo.jpg') }}" alt="ISC KINDU">
        <div>
            <div class="registry-country">Republique Democratique du Congo</div>
            <h2>Institut Superieur de Commerce de Kindu</h2>
            <p>ISC KINDU</p>
            <h1>LISTE DES DIPLOMES</h1>
            <div class="muted">{{ $graduationList->title }}</div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Informations</h2>
            <div class="actions">
                <a class="btn btn-muted" href="{{ route('admin.graduations.index') }}">Retour</a>
                <a class="btn btn-muted" href="{{ route('admin.graduations.edit', $graduationList) }}">Modifier</a>
                <a class="btn btn-secondary" href="{{ route('admin.graduations.export', $graduationList) }}">Exporter CSV</a>
                <button class="btn btn-muted" type="button" onclick="window.print()">Imprimer</button>
            </div>
        </div>
        <div class="card-body">
            <dl class="check-list">
                <div><dt>Annee academique</dt><dd>{{ $graduationList->academicYear?->code }}</dd></div>
                <div><dt>Section</dt><dd>{{ $graduationList->section?->name }}</dd></div>
                <div><dt>Option/Filiere</dt><dd>{{ $graduationList->program?->name }}</dd></div>
                <div><dt>Promotion</dt><dd>{{ $graduationList->promotion?->name }}</dd></div>
                <div><dt>Cycle</dt><dd>{{ $graduationList->cycle }}</dd></div>
                <div><dt>Date de decision</dt><dd>{{ $graduationList->decision_date?->format('d/m/Y') }}</dd></div>
                <div><dt>Date de publication</dt><dd>{{ $graduationList->published_at?->format('d/m/Y') }}</dd></div>
                <div><dt>Statut</dt><dd>{{ $graduationList->status }}</dd></div>
            </dl>
        </div>
    </section>

    <section class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2 class="card-title">Etudiants diplomes</h2>
            <span class="badge">{{ $graduationList->graduates->count() }} etudiant(s)</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Numero</th><th>Matricule</th><th>Nom</th><th>Postnom</th><th>Prenom</th><th>Sexe</th><th>Pourcentage</th><th>Mention</th></tr></thead>
                <tbody>
                    @forelse ($graduationList->graduates as $graduate)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $graduate->matricule }}</td>
                            <td>{{ $graduate->last_name }}</td>
                            <td>{{ $graduate->post_name }}</td>
                            <td>{{ $graduate->first_name }}</td>
                            <td>{{ $graduate->gender }}</td>
                            <td>{{ $graduate->percentage }}</td>
                            <td>{{ $graduate->mention }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted">Aucun diplome dans cette liste.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
