@extends('admin.layout')

@section('title', $notification->exists ? 'Modifier une notification' : 'Nouvelle notification')
@section('subtitle', 'Informer les etudiants depuis le backend')

@section('content')
    <form class="card" method="post" action="{{ $notification->exists ? route('admin.student-notifications.update', $notification) : route('admin.student-notifications.store') }}">
        @csrf
        @if ($notification->exists)
            @method('PUT')
        @endif

        <div class="card-body form-grid">
            @unless ($notification->exists)
                <label class="field full checkbox-row">
                    <input type="checkbox" name="send_to_all" value="1">
                    Envoyer a tous les etudiants actifs
                </label>
            @endunless

            <label class="field full">
                <span>Etudiant</span>
                <select name="user_id">
                    <option value="">Choisir un etudiant ou cocher l envoi a tous</option>
                    @foreach ($studentUsers as $user)
                        <option value="{{ $user->id }}" @selected((int) old('user_id', $notification->user_id) === $user->id)>
                            {{ $user->name }} - {{ $user->studentProfile?->matricule ?? $user->email }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Dossier inscription lie</span>
                <select name="admission_application_id">
                    <option value="">Aucun</option>
                    @foreach ($applications as $application)
                        <option value="{{ $application->id }}" @selected((int) old('admission_application_id', $notification->admission_application_id) === $application->id)>
                            {{ $application->application_number }} - {{ $application->user?->name ?? $application->full_name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Type</span>
                <select name="type" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('type', $notification->type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field full">
                <span>Titre</span>
                <input name="title" value="{{ old('title', $notification->title) }}" required>
            </label>

            <label class="field full">
                <span>Message</span>
                <textarea name="message" required>{{ old('message', $notification->message) }}</textarea>
            </label>
        </div>

        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.student-notifications.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
