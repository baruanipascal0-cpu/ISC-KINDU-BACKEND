@extends('admin.layout')

@section('title', 'Fiche individuelle')
@section('subtitle', $enrollment->enrollment_number)

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Inscription officielle</h2>
            <div class="actions">
                <a class="btn btn-muted" href="{{ route('admin.registry.index') }}">Retour</a>
                @if ($enrollment->fiche_path)
                    <a class="btn btn-secondary" href="{{ asset('storage/'.$enrollment->fiche_path) }}" target="_blank">Ouvrir la fiche</a>
                @endif
                <button class="btn btn-muted" type="button" onclick="window.print()">Imprimer</button>
            </div>
        </div>
        <div class="card-body">
            <dl class="check-list">
                <div><dt>Numero d inscription</dt><dd>{{ $enrollment->enrollment_number }}</dd></div>
                <div><dt>Matricule</dt><dd>{{ $enrollment->student?->matricule }}</dd></div>
                <div><dt>Nom complet</dt><dd>{{ $enrollment->student?->full_name }}</dd></div>
                <div><dt>Sexe</dt><dd>{{ $enrollment->student?->gender }}</dd></div>
                <div><dt>Annee academique</dt><dd>{{ $enrollment->academicYear?->code }}</dd></div>
                <div><dt>Section</dt><dd>{{ $enrollment->section?->name }}</dd></div>
                <div><dt>Filiere</dt><dd>{{ $enrollment->program?->name }}</dd></div>
                <div><dt>Promotion</dt><dd>{{ $enrollment->promotion?->name ?? $enrollment->level?->name }}</dd></div>
                <div><dt>Date</dt><dd>{{ $enrollment->enrolled_on?->format('d/m/Y') }}</dd></div>
                <div><dt>Statut</dt><dd>{{ $enrollment->status }}</dd></div>
            </dl>
        </div>
    </section>
@endsection
