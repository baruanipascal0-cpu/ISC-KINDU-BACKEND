@extends('admin.layout')

@section('title', $page->exists ? 'Modifier une page' : 'Nouvelle page')
@section('subtitle', 'Contenu statique publie vers le site')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if ($page->exists)
            @method('PUT')
        @endif
        <div class="card-body form-grid">
            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="{{ old('title', $page->title) }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" placeholder="exemple: diplomes">
            </div>
            <div class="field full">
                <label for="excerpt">Resume</label>
                <textarea id="excerpt" name="excerpt">{{ old('excerpt', $page->excerpt) }}</textarea>
            </div>
            <div class="field full">
                <label for="body">Contenu</label>
                <textarea id="body" name="body" style="min-height: 260px;">{{ old('body', $page->body) }}</textarea>
            </div>
            <div class="field">
                <label for="image_url">Image URL</label>
                <input id="image_url" name="image_url" value="{{ old('image_url', $page->image_url) }}">
            </div>
            <div class="field">
                <label for="image_file">Ou image</label>
                <input id="image_file" name="image_file" type="file" accept="image/*">
            </div>
            <div class="field">
                <label for="image_alt">Texte alternatif image</label>
                <input id="image_alt" name="image_alt" value="{{ old('image_alt', $page->image_alt) }}">
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))>
                Publier sur le site
            </label>
        </div>
        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.pages.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
