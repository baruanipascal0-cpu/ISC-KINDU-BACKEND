@extends('admin.layout')

@section('title', $banner->exists ? 'Modifier une banniere' : 'Nouvelle banniere')
@section('subtitle', 'Publier une image et un message dans le diaporama de la page d accueil')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}">
        @csrf
        @if ($banner->exists)
            @method('PUT')
        @endif

        <div class="card-body form-grid">
            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="{{ old('title', $banner->title) }}" required>
            </div>
            <div class="field">
                <label for="subtitle">Sous-titre ou badge</label>
                <input id="subtitle" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}">
            </div>
            <div class="field full">
                <label for="body">Texte court</label>
                <textarea id="body" name="body">{{ old('body', $banner->body) }}</textarea>
            </div>
            <div class="field">
                <label for="image_file">Image banniere</label>
                <input id="image_file" name="image_file" type="file" accept="image/*">
            </div>
            <div class="field">
                <label for="image_url">Ou URL image</label>
                <input id="image_url" name="image_url" value="{{ old('image_url', $banner->image_url) }}">
            </div>
            <div class="field">
                <label for="image_alt">Texte alternatif image</label>
                <input id="image_alt" name="image_alt" value="{{ old('image_alt', $banner->image_alt) }}">
            </div>
            <div class="field">
                <label for="link_url">Lien du bouton</label>
                <input id="link_url" name="link_url" value="{{ old('link_url', $banner->link_url) }}" placeholder="/blog.html">
            </div>
            <div class="field">
                <label for="link_label">Texte du bouton</label>
                <input id="link_label" name="link_label" value="{{ old('link_label', $banner->link_label) }}" placeholder="Lire la suite">
            </div>
            <div class="field">
                <label for="sort_order">Ordre</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $banner->sort_order ?? 0) }}">
            </div>
            <div class="field">
                <label for="key">Cle technique</label>
                <input id="key" name="key" value="{{ old('key', $banner->key) }}" placeholder="generee automatiquement">
            </div>

            @if ($banner->image_url)
                <label class="checkbox-row">
                    <input type="checkbox" name="clear_image" value="1">
                    Retirer l image actuelle
                </label>
            @endif

            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active))>
                Visible sur le site
            </label>
        </div>

        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.banners.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
