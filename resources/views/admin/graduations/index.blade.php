@extends('admin.layout')

@section('title', 'Diplomes')
@section('subtitle', 'Listes officielles des etudiants ayant termine leurs etudes')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Filtres</h2>
            <div class="actions">
                <a class="btn btn-muted" href="{{ route('admin.graduations.index') }}">Reinitialiser</a>
                <a class="btn btn-primary" href="{{ route('admin.graduations.create') }}">Nouvelle liste</a>
            </div>
        </div>
        <div class="card-body">
            <form method="get" class="form-grid">
                <label class="field">
                    <span>Annee academique</span>
                    <select name="academic_year_id">
                        <option value="">Toutes</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? '') == $year->id)>{{ $year->code }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Section</span>
                    <select name="section_id">
                        <option value="">Toutes</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected(($filters['section_id'] ?? '') == $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Option/Filiere</span>
                    <select name="program_id">
                        <option value="">Toutes</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') == $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Promotion</span>
                    <select name="promotion_id">
                        <option value="">Toutes</option>
                        @foreach ($promotions as $promotion)
                            <option value="{{ $promotion->id }}" @selected(($filters['promotion_id'] ?? '') == $promotion->id)>{{ $promotion->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Statut</span>
                    <select name="status">
                        <option value="">Tous</option>
                        @foreach (['draft' => 'Brouillon', 'published' => 'Publie', 'archived' => 'Archive'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="field">
                    <span>&nbsp;</span>
                    <button class="btn btn-secondary" type="submit">Appliquer</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2 class="card-title">Listes de diplomes</h2>
            <span class="badge">{{ $lists->total() }} liste(s)</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Annee</th>
                        <th>Section</th>
                        <th>Option/Filiere</th>
                        <th>Promotion</th>
                        <th>Diplomes</th>
                        <th>Publication</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lists as $list)
                        <tr>
                            <td>{{ $list->title }}</td>
                            <td>{{ $list->academicYear?->code }}</td>
                            <td>{{ $list->section?->name }}</td>
                            <td>{{ $list->program?->name }}</td>
                            <td>{{ $list->promotion?->name }}</td>
                            <td>{{ $list->graduates_count }}</td>
                            <td>{{ $list->published_at?->format('d/m/Y') }}</td>
                            <td><span class="badge">{{ $list->status }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.graduations.show', $list) }}">Voir</a>
                                    <a class="btn btn-muted" href="{{ route('admin.graduations.edit', $list) }}">Modifier</a>
                                    @if ($list->status === 'published')
                                        <a class="btn btn-secondary" href="/diplomes/{{ $list->slug }}" target="_blank">Site</a>
                                    @endif
                                    <form method="post" action="{{ route('admin.graduations.destroy', $list) }}" onsubmit="return confirm('Supprimer cette liste ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted">Aucune liste de diplomes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $lists->links() }}</div>
    </section>
@endsection
