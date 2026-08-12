@extends('admin.layout')

@section('title', $type ? $type : 'Publications du site')
@section('subtitle', $type ? 'Contenus de type '.$type.' relies a la structure ISC Kindu' : 'Documents, frais, recherche, opportunites et autres contenus publics')

@section('content')
    @php
        $publicUrl = function ($publication): string {
            return match ($publication->type) {
                'Frais' => '/nos-frais.html',
                'Diplome' => '/nos-diplomes.html',
                'Alumni' => '/page/alumni.html',
                'Opportunite', 'Offre', 'Emploi', 'Stage' => '/travailler-a-isc/opportunites.html',
                'Article', 'Article scientifique', 'These', 'Formation enseignant', 'Conference', 'Seminaire', 'Centre de recherche', 'Projet', 'Recherche', 'Hackathon', 'Realisation etudiant', 'Travail Licence', 'Travail Master', 'Travail etudiant', 'Travail enseignant', 'Stage academique' => '/services-a-la-societe.html',
                default => '/documents.html',
            };
        };
    @endphp

    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">{{ $type ? $type : 'Toutes les publications' }}</h2>
                <div class="admin-tabs">
                    <a href="{{ route('admin.publications.index') }}" @class(['active' => ! $type])>Toutes</a>
                    @foreach ($publicationTypeGroups as $groupLabel => $items)
                        @foreach ($items as $item)
                            <a href="{{ route('admin.publications.index', ['type' => $item]) }}" @class(['active' => $type === $item])>{{ $item }}</a>
                        @endforeach
                    @endforeach
                </div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.publications.create', ['type' => $type]) }}">Nouvelle publication</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Titre</th><th>Type</th><th>Date</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($publications as $publication)
                        <tr>
                            <td>{{ $publication->title }}</td>
                            <td>{{ $publication->type }}</td>
                            <td>{{ optional($publication->published_at)->format('d/m/Y') }}</td>
                            <td><span class="badge">{{ $publication->is_published ? 'publiee' : 'brouillon' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.publications.edit', $publication) }}">Modifier</a>
                                    @if ($publication->is_published)
                                        <a class="btn btn-secondary" href="{{ $publicUrl($publication) }}" target="_blank">Site</a>
                                    @endif
                                    <form method="post" action="{{ route('admin.publications.destroy', $publication) }}" onsubmit="return confirm('Supprimer cette publication ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucune publication.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $publications->links() }}</div>
    </section>
@endsection
