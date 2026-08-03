@extends('admin.layout')

@section('title', $event->exists ? 'Modifier un evenement' : 'Nouvel evenement')
@section('subtitle', 'Publier un element d agenda')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}">
        @csrf
        @if ($event->exists)
            @method('PUT')
        @endif
        <div class="card-body form-grid">
            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="{{ old('title', $event->title) }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $event->slug) }}">
            </div>
            <div class="field">
                <label for="location">Lieu</label>
                <input id="location" name="location" value="{{ old('location', $event->location) }}">
            </div>
            <div class="field">
                <label for="starts_at">Debut</label>
                <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', optional($event->starts_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="field">
                <label for="ends_at">Fin</label>
                <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', optional($event->ends_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="field">
                <label for="image_file">Image</label>
                <input id="image_file" name="image_file" type="file" accept="image/*">
            </div>
            <div class="field full">
                <label for="description">Description detaillee</label>
                <textarea id="description" name="description" style="min-height: 220px;">{{ old('description', $event->description) }}</textarea>
            </div>
            <div class="field">
                <label for="image_url">Ou URL image</label>
                <input id="image_url" name="image_url" value="{{ old('image_url', $event->image_url) }}">
            </div>
            <div class="field">
                <label for="image_alt">Texte alternatif image</label>
                <input id="image_alt" name="image_alt" value="{{ old('image_alt', $event->image_alt) }}">
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->is_published))>
                Publier sur le site
            </label>
        </div>
        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.events.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
