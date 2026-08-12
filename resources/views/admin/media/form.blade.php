@extends('admin.layout')

@section('title', $media->exists ? 'Modifier un media' : 'Nouveau media')
@section('subtitle', 'Alimenter la galerie, les photos et les fichiers publics du site')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $media->exists ? route('admin.media.update', $media) : route('admin.media.store') }}">
        @csrf
        @if ($media->exists)
            @method('PUT')
        @endif
        <div class="card-body form-grid">
            <div class="field">
                <label for="name">Nom</label>
                <input id="name" name="name" value="{{ old('name', $media->name) }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $media->slug) }}">
            </div>
            <div class="field">
                <label for="collection">Collection</label>
                <input id="collection" name="collection" value="{{ old('collection', $media->collection ?: 'gallery') }}" required>
            </div>
            <div class="field">
                <label for="published_at">Date de publication</label>
                <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', optional($media->published_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="field full">
                <label for="caption">Legende</label>
                <textarea id="caption" name="caption">{{ old('caption', $media->caption) }}</textarea>
            </div>
            <div class="field">
                <label for="file">Fichier a envoyer</label>
                <input id="file" name="file" type="file">
            </div>
            <div class="field">
                <label for="path">Ou URL existante</label>
                <input id="path" name="path" value="{{ old('path', $media->path) }}">
            </div>
            <div class="field">
                <label for="alt_text">Texte alternatif</label>
                <input id="alt_text" name="alt_text" value="{{ old('alt_text', $media->alt_text) }}">
            </div>
            <div class="field">
                <label for="sort_order">Ordre</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $media->sort_order ?? 0) }}">
            </div>
            <div class="field full">
                <label for="metadata">Metadata JSON</label>
                <textarea id="metadata" name="metadata" placeholder='{"page":"media-center"}'>{{ old('metadata', $metadataText) }}</textarea>
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $media->is_published))>
                Publier sur le site
            </label>
        </div>
        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.media.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
