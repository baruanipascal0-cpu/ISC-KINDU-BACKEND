@extends('admin.layout')

@section('title', $publication->exists ? 'Modifier une publication' : 'Nouvelle publication')
@section('subtitle', 'Publier les documents visibles dans ressources, diplomes et publications')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $publication->exists ? route('admin.publications.update', $publication) : route('admin.publications.store') }}">
        @csrf
        @if ($publication->exists)
            @method('PUT')
        @endif
        <div class="card-body form-grid">
            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="{{ old('title', $publication->title) }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $publication->slug) }}">
            </div>
            <div class="field">
                <label for="type">Type</label>
                <select id="type" name="type">
                    @foreach (['Document', 'Communique', 'Article', 'Ressource', 'Bibliotheque', 'These', 'Centre de recherche', 'In memoriam', 'Alumni'] as $type)
                        <option value="{{ $type }}" @selected(old('type', $publication->type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="published_at">Date de publication</label>
                <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', optional($publication->published_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="field full">
                <label for="description">Description detaillee</label>
                <textarea id="description" name="description" style="min-height: 220px;">{{ old('description', $publication->description) }}</textarea>
            </div>
            <div class="field">
                <label for="file">Fichier</label>
                <input id="file" name="file" type="file">
            </div>
            <div class="field">
                <label for="file_url">Ou URL fichier</label>
                <input id="file_url" name="file_url" value="{{ old('file_url', $publication->file_url) }}">
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $publication->is_published))>
                Publier sur le site
            </label>
        </div>
        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.publications.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
