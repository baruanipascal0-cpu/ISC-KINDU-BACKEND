@extends('admin.layout')

@section('title', 'Inscriptions')
@section('subtitle', 'Dossiers envoyes par les candidats')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Dossiers</h2>
            <form method="get" class="actions">
                <select name="status">
                    <option value="">Tous les etats</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item }}" @selected($status === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                <button class="btn btn-muted" type="submit">Filtrer</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Candidat</th>
                        <th>Section/Filiere</th>
                        <th>Contact</th>
                        <th>Etat</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($applications as $application)
                    <tr>
                        <td>{{ $application->application_number }}</td>
                        <td>
                            <strong>{{ $application->first_name }} {{ $application->post_name }} {{ $application->last_name }}</strong>
                            <div class="muted">
                                {{ $application->academicYear?->code ?? $application->academic_year }}
                                /
                                {{ $application->promotion?->name ?? $application->academicLevel?->name ?? $application->level }}
                            </div>
                        </td>
                        <td>
                            {{ $application->section?->name ?? 'Section non definie' }}
                            <div class="muted">{{ $application->program?->name ?? 'Filiere non definie' }}</div>
                        </td>
                        <td>
                            {{ $application->email }}
                            <div class="muted">{{ $application->phone }}</div>
                        </td>
                        <td><span class="badge">{{ $application->status }}</span></td>
                        <td>
                            <a class="btn btn-secondary" href="{{ route('admin.admissions.show', $application) }}">Controler</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Aucun dossier.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $applications->links() }}</div>
    </section>
@endsection
