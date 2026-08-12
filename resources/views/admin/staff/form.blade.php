@extends('admin.layout')

@section('title', $staffMember->exists ? 'Modifier un membre' : 'Nouveau membre')
@section('subtitle', 'Completer les informations visibles dans les pages enseignants et institution')

@section('content')
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $staffMember->exists ? route('admin.staff.update', $staffMember) : route('admin.staff.store') }}">
        @csrf
        @if ($staffMember->exists)
            @method('PUT')
        @endif
        <div class="card-body form-grid">
            <div class="field">
                <label for="name">Nom complet</label>
                <input id="name" name="name" value="{{ old('name', $staffMember->name) }}" required>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $staffMember->slug) }}">
            </div>
            <div class="field">
                <label for="title">Titre</label>
                <input id="title" name="title" value="{{ old('title', $staffMember->title) }}">
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    @foreach (['enseignant', 'direction', 'administration', 'chercheur', 'service'] as $role)
                        <option value="{{ $role }}" @selected(old('role', $staffMember->role) === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="department">Departement / service</label>
                <input id="department" name="department" value="{{ old('department', $staffMember->department) }}">
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $staffMember->email) }}">
            </div>
            <div class="field">
                <label for="phone">Telephone</label>
                <input id="phone" name="phone" value="{{ old('phone', $staffMember->phone) }}">
            </div>
            <div class="field">
                <label for="sort_order">Ordre</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $staffMember->sort_order ?? 0) }}">
            </div>
            <div class="field full">
                <label for="biography">Biographie</label>
                <textarea id="biography" name="biography" style="min-height: 180px;">{{ old('biography', $staffMember->biography) }}</textarea>
            </div>
            <div class="field">
                <label for="image_file">Photo</label>
                <input id="image_file" name="image_file" type="file" accept="image/*">
            </div>
            <div class="field">
                <label for="image_url">Ou URL photo</label>
                <input id="image_url" name="image_url" value="{{ old('image_url', $staffMember->image_url) }}">
            </div>
            <div class="field">
                <label for="image_alt">Texte alternatif photo</label>
                <input id="image_alt" name="image_alt" value="{{ old('image_alt', $staffMember->image_alt) }}">
            </div>
            <div class="field full">
                <label for="metadata">Metadata JSON</label>
                <textarea id="metadata" name="metadata" placeholder='{"specialite":"Comptabilite"}'>{{ old('metadata', $metadataText) }}</textarea>
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $staffMember->is_active))>
                Afficher sur le site
            </label>
        </div>
        <div class="card-header">
            <a class="btn btn-muted" href="{{ route('admin.staff.index') }}">Retour</a>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
