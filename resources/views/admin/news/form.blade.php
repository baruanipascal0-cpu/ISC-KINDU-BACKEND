@extends('admin.layout')

@section('title', $post->exists ? 'Modifier une actualite' : 'Publier une actualite')
@section('subtitle', 'Ces contenus alimentent la page Actualites')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $post->exists ? route('admin.news.update', $post) : route('admin.news.store') }}">
        @csrf
        @if ($post->exists)
            @method('PUT')
        @endif
        <div class="card-body form-grid">
            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="{{ old('title', $post->title) }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $post->slug) }}">
            </div>
            <div class="field">
                <label for="category">Categorie</label>
                <input id="category" name="category" value="{{ old('category', $post->category ?: 'Actualites') }}">
            </div>
            <div class="field">
                <label for="published_at">Date de publication</label>
                <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', optional($post->published_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="field full">
                <label for="excerpt">Resume</label>
                <textarea id="excerpt" name="excerpt">{{ old('excerpt', $post->excerpt) }}</textarea>
            </div>
            <div class="field full">
                <label for="body">Contenu</label>
                <textarea id="body" name="body" style="min-height: 260px;">{{ old('body', $post->body) }}</textarea>
            </div>
            <div class="field">
                <label for="image_file">Image</label>
                <input id="image_file" name="image_file" type="file" accept="image/*">
            </div>
            <div class="field">
                <label for="image_url">Ou URL image</label>
                <input id="image_url" name="image_url" value="{{ old('image_url', $post->image_url) }}">
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $post->is_published))>
                Publier sur le site
            </label>
        </div>
        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.news.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
