@extends('admin.layout')

@section('title', 'Registre des inscriptions')
@section('subtitle', 'Liste officielle triee par promotion et option')

@section('content')
    <section class="registry-official-head">
        <img src="{{ asset('images/site/logo.jpg') }}" alt="ISC KINDU">
        <div>
            <div class="registry-country">Republique Democratique du Congo</div>
            <h2>Institut Superieur de Commerce de Kindu</h2>
            <p>ISC KINDU</p>
            <h1>LISTE D'INSCRIPTION</h1>
        </div>
    </section>

    <section class="card registry-list-card">
        <div class="card-header registry-list-header">
            <h2 class="card-title">Registre officiel</h2>
            <div class="actions registry-screen-actions">
                <span class="badge">{{ $enrollments->total() }} ligne(s)</span>
                <button class="btn btn-muted" type="button" onclick="window.print()">Imprimer</button>
                <a class="btn btn-secondary" href="{{ route('admin.registry.export', request()->query()) }}">Exporter CSV</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Numero inscription</th>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Postnom</th>
                        <th>Prenom</th>
                        <th>Sexe</th>
                        <th>Annee</th>
                        <th>Section</th>
                        <th>Option/Filiere</th>
                        <th>Promotion</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Fiche</th>
                    </tr>
                </thead>
                <tbody>
                @php $currentGroup = null; @endphp
                @forelse ($enrollments as $enrollment)
                    @php
                        $promotionLabel = $enrollment->promotion?->name ?? $enrollment->level?->name ?? 'Sans promotion';
                        $programLabel = $enrollment->program?->name ?? 'Sans option';
                        $groupKey = $promotionLabel.'|'.$programLabel;
                    @endphp
                    @if ($groupKey !== $currentGroup)
                        @php $currentGroup = $groupKey; @endphp
                        <tr class="registry-group-row">
                            <td colspan="14">
                                <strong>Promotion:</strong> {{ $promotionLabel }}
                                <span>-</span>
                                <strong>Option/Filiere:</strong> {{ $programLabel }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ $loop->iteration + ($enrollments->currentPage() - 1) * $enrollments->perPage() }}</td>
                        <td>{{ $enrollment->enrollment_number }}</td>
                        <td>{{ $enrollment->student?->matricule }}</td>
                        <td>{{ $enrollment->student?->last_name }}</td>
                        <td>{{ $enrollment->student?->post_name }}</td>
                        <td>{{ $enrollment->student?->first_name }}</td>
                        <td>{{ $enrollment->student?->gender }}</td>
                        <td>{{ $enrollment->academicYear?->code }}</td>
                        <td>{{ $enrollment->section?->name }}</td>
                        <td>{{ $programLabel }}</td>
                        <td>{{ $promotionLabel }}</td>
                        <td>{{ $enrollment->enrolled_on?->format('d/m/Y') }}</td>
                        <td><span class="badge">{{ $enrollment->status }}</span></td>
                        <td><a class="btn btn-muted" href="{{ route('admin.registry.show', $enrollment) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="muted">Aucune inscription officielle.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $enrollments->links() }}</div>
    </section>

    <details class="card registry-tools">
        <summary>Filtres et export</summary>
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
                    <span>Filiere</span>
                    <select name="program_id">
                        <option value="">Toutes</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') == $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Niveau</span>
                    <select name="level_id">
                        <option value="">Tous</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}" @selected(($filters['level_id'] ?? '') == $level->id)>{{ $level->name }}</option>
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
                    <span>Sexe</span>
                    <select name="gender">
                        <option value="">Tous</option>
                        <option value="M" @selected(($filters['gender'] ?? '') === 'M')>M</option>
                        <option value="F" @selected(($filters['gender'] ?? '') === 'F')>F</option>
                    </select>
                </label>
                <label class="field">
                    <span>Type d inscription</span>
                    <select name="type">
                        <option value="">Tous</option>
                        <option value="nouvelle_inscription" @selected(($filters['type'] ?? '') === 'nouvelle_inscription')>Nouvelle inscription</option>
                        <option value="reinscription" @selected(($filters['type'] ?? '') === 'reinscription')>Reinscription</option>
                    </select>
                </label>
                <label class="field">
                    <span>Statut</span>
                    <select name="status">
                        <option value="">Tous</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Actif</option>
                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Annule</option>
                    </select>
                </label>
                <label class="field">
                    <span>Date debut</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </label>
                <label class="field">
                    <span>Date fin</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </label>
                <label class="field full">
                    <span>Nom ou matricule</span>
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, postnom, prenom, matricule ou numero">
                </label>
                <div class="field full registry-tool-actions">
                    <button class="btn btn-primary" type="submit">Appliquer les filtres</button>
                    <a class="btn btn-muted" href="{{ route('admin.registry.index') }}">Reinitialiser</a>
                    <a class="btn btn-secondary" href="{{ route('admin.registry.export', request()->query()) }}">Exporter CSV</a>
                </div>
            </form>
        </div>
    </details>
@endsection
