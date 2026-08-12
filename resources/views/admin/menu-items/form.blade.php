@extends('admin.layout')

@section('title', $item->exists ? 'Modifier un lien' : 'Nouveau lien')
@section('subtitle', 'Controler la navigation exposee par l API')

@section('content')
    <form class="card" method="post" action="{{ $item->exists ? route('admin.menu-items.update', $item) : route('admin.menu-items.store') }}">
        @csrf
        @if ($item->exists)
            @method('PUT')
        @endif
        <div class="card-body form-grid">
            <div class="field">
                <label for="label">Libelle</label>
                <input id="label" name="label" value="{{ old('label', $item->label) }}" required>
            </div>
            <div class="field">
                <label for="url">URL</label>
                <input id="url" name="url" value="{{ old('url', $item->url) }}" required>
            </div>
            <div class="field">
                <label for="location">Menu</label>
                <select id="location" name="location">
                    @foreach (['main', 'topbar', 'footer', 'student', 'institution', 'formation'] as $location)
                        <option value="{{ $location }}" @selected(old('location', $item->location) === $location)>{{ $location }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="parent_id">Parent</label>
                <select id="parent_id" name="parent_id">
                    <option value="">Aucun</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $item->parent_id) === (string) $parent->id)>
                            {{ $parent->location }} - {{ $parent->label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="sort_order">Ordre</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))>
                Afficher dans l API
            </label>
        </div>
        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.menu-items.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
