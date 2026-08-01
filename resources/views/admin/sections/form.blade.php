@extends('admin.layout')

@section('title', $section->exists ? 'Modifier une section' : 'Nouvelle section')
@section('subtitle', 'Une filiere par ligne : Nom | Cycle | Description')

@section('content')
    <form class="card" method="post" action="{{ $section->exists ? route('admin.sections.update', $section) : route('admin.sections.store') }}">
        @csrf
        @if ($section->exists)
            @method('PUT')
        @endif

        <div class="card-body form-grid">
            <div class="field">
                <label for="name">Nom de la section</label>
                <input id="name" name="name" value="{{ old('name', $section->name) }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $section->slug) }}">
            </div>
            <div class="field">
                <label for="sort_order">Ordre</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $section->sort_order ?? 0) }}">
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $section->is_active))>
                Visible sur le site
            </label>
            <div class="field full">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{ old('description', $section->description) }}</textarea>
            </div>
            <div class="field full">
                <label for="programs_text">Filieres</label>
                <textarea id="programs_text" name="programs_text" style="min-height: 260px;" placeholder="Comptabilite et finances | Licence | Description demo">{{ old('programs_text', $programsText) }}</textarea>
                <p class="muted">Les lignes existantes sont creees ou mises a jour automatiquement.</p>
            </div>
        </div>

        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.sections.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
