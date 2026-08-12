@extends('admin.layout')

@section('title', $graduationList->exists ? 'Modifier la liste de diplomes' : 'Nouvelle liste de diplomes')
@section('subtitle', 'Publier une liste officielle par annee, promotion et option')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $graduationList->exists ? route('admin.graduations.update', $graduationList) : route('admin.graduations.store') }}">
        @csrf
        @if ($graduationList->exists)
            @method('PUT')
        @endif

        <div class="card-body form-grid">
            <label class="field full">
                <span>Titre</span>
                <input name="title" value="{{ old('title', $graduationList->title) }}" required placeholder="Liste des diplomes Licence 3 Informatique de gestion">
            </label>
            <label class="field">
                <span>Slug</span>
                <input name="slug" value="{{ old('slug', $graduationList->slug) }}" placeholder="genere automatiquement si vide">
            </label>
            <label class="field">
                <span>Cycle</span>
                <input name="cycle" value="{{ old('cycle', $graduationList->cycle) }}" placeholder="Licence, Master...">
            </label>
            <label class="field">
                <span>Annee academique</span>
                <select name="academic_year_id">
                    <option value="">Non precisee</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(old('academic_year_id', $graduationList->academic_year_id) == $year->id)>{{ $year->code }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Section</span>
                <select name="section_id">
                    <option value="">Non precisee</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected(old('section_id', $graduationList->section_id) == $section->id)>{{ $section->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Option/Filiere</span>
                <select name="program_id">
                    <option value="">Non precisee</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected(old('program_id', $graduationList->program_id) == $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Promotion</span>
                <select name="promotion_id">
                    <option value="">Non precisee</option>
                    @foreach ($promotions as $promotion)
                        <option value="{{ $promotion->id }}" @selected(old('promotion_id', $graduationList->promotion_id) == $promotion->id)>{{ $promotion->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Date de decision</span>
                <input name="decision_date" type="date" value="{{ old('decision_date', $graduationList->decision_date?->format('Y-m-d')) }}">
            </label>
            <label class="field">
                <span>Date de publication</span>
                <input name="published_at" type="datetime-local" value="{{ old('published_at', $graduationList->published_at?->format('Y-m-d\TH:i')) }}">
            </label>
            <label class="field">
                <span>Statut</span>
                <select name="status">
                    @foreach (['draft' => 'Brouillon', 'published' => 'Publie', 'archived' => 'Archive'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $graduationList->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field full">
                <span>Etudiants diplomes</span>
                <textarea name="graduates_text" rows="12" placeholder="Matricule;Nom;Postnom;Prenom;Sexe;Pourcentage;Mention">{{ old('graduates_text', $graduatesText) }}</textarea>
                <span class="muted">Une ligne par etudiant. Exemple: ISC-2026-0001;KASONGO;MUTOMBO;Jean;M;75;Distinction</span>
            </label>
            <label class="field full">
                <span>Importer une liste CSV/TXT</span>
                <input name="graduates_file" type="file" accept=".csv,.txt,text/csv,text/plain">
                <span class="muted">Colonnes attendues: Matricule;Nom;Postnom;Prenom;Sexe;Pourcentage;Mention. Depuis Excel, enregistrer le tableau en CSV UTF-8 avant l'import.</span>
            </label>
        </div>

        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.graduations.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
