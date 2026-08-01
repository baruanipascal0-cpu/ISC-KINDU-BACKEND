@extends('admin.layout')

@section('title', $block->exists ? 'Modifier un bloc' : 'Nouveau bloc')
@section('subtitle', 'Ces donnees remplacent les cartes et textes publics du site')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $block->exists ? route('admin.content-blocks.update', $block) : route('admin.content-blocks.store') }}">
        @csrf
        @if ($block->exists)
            @method('PUT')
        @endif

        <div class="card-body form-grid">
            <div class="field">
                <label for="block_group">Groupe</label>
                <select id="block_group" name="block_group" required>
                    @foreach ($knownGroups as $value => $label)
                        <option value="{{ $value }}" @selected(old('block_group', $block->block_group) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="key">Cle technique</label>
                <input id="key" name="key" value="{{ old('key', $block->key) }}" placeholder="generee automatiquement">
            </div>
            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="{{ old('title', $block->title) }}">
            </div>
            <div class="field">
                <label for="subtitle">Court texte</label>
                <input id="subtitle" name="subtitle" value="{{ old('subtitle', $block->subtitle) }}">
            </div>
            <div class="field full">
                <label for="body">Contenu</label>
                <textarea id="body" name="body">{{ old('body', $block->body) }}</textarea>
            </div>
            <div class="field">
                <label for="image_file">Image</label>
                <input id="image_file" name="image_file" type="file">
            </div>
            <div class="field">
                <label for="image_url">Ou URL image</label>
                <input id="image_url" name="image_url" value="{{ old('image_url', $block->image_url) }}">
            </div>
            <div class="field">
                <label for="link_url">Lien</label>
                <input id="link_url" name="link_url" value="{{ old('link_url', $block->link_url) }}">
            </div>
            <div class="field">
                <label for="link_label">Libelle du lien</label>
                <input id="link_label" name="link_label" value="{{ old('link_label', $block->link_label) }}">
            </div>
            <div class="field">
                <label for="icon">Icone Font Awesome</label>
                <input id="icon" name="icon" value="{{ old('icon', $block->icon) }}" placeholder="fas fa-user-plus">
            </div>
            <div class="field">
                <label for="sort_order">Ordre</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $block->sort_order ?? 0) }}">
            </div>
            <div class="field full">
                <label for="metadata">Metadata JSON</label>
                <textarea id="metadata" name="metadata" placeholder='{"badge":"Demo"}'>{{ old('metadata', $metadataText) }}</textarea>
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $block->is_active))>
                Visible sur le site
            </label>
        </div>

        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.content-blocks.index', ['group' => old('block_group', $block->block_group)]) }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
