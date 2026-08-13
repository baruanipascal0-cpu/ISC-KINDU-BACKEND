@extends('admin.layout')

@section('title', $document->exists ? 'Modifier un document etudiant' : 'Nouveau document etudiant')
@section('subtitle', 'Le fichier sera visible dans l espace etudiant')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $document->exists ? route('admin.student-documents.update', $document) : route('admin.student-documents.store') }}">
        @csrf
        @if ($document->exists)
            @method('PUT')
        @endif

        <div class="card-body form-grid">
            <label class="field full">
                <span>Etudiant</span>
                <select name="user_id" required>
                    <option value="">Choisir un etudiant</option>
                    @foreach ($studentUsers as $user)
                        <option value="{{ $user->id }}" @selected((int) old('user_id', $document->user_id) === $user->id)>
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
                        <option value="{{ $application->id }}" @selected((int) old('admission_application_id', $document->admission_application_id) === $application->id)>
                            {{ $application->application_number }} - {{ $application->user?->name ?? $application->full_name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Nom du document</span>
                <input name="name" value="{{ old('name', $document->name) }}" required placeholder="Ex : Fiche d inscription">
            </label>

            <label class="field">
                <span>Type</span>
                <select name="type" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('type', $document->type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Statut</span>
                <select name="status" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $document->status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Date de publication</span>
                <input name="issued_at" type="datetime-local" value="{{ old('issued_at', optional($document->issued_at)->format('Y-m-d\TH:i')) }}">
            </label>

            <label class="field">
                <span>Fichier</span>
                <input name="file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.webp,.txt">
            </label>

            @if ($document->file_path)
                <label class="checkbox-row">
                    <input type="checkbox" name="clear_file" value="1">
                    Retirer le fichier actuel
                </label>
            @endif
        </div>

        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.student-documents.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
