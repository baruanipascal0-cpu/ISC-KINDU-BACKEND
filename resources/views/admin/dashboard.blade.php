@extends('admin.layout')

@section('title', 'Tableau de bord')
@section('subtitle', 'Administration alignee sur la structure actuelle du site ISC Kindu')

@section('content')
    <div class="grid stats-grid">
        @foreach ($stats as $label => $value)
            <article class="card">
                <div class="card-body">
                    <p class="stat-value">{{ $value }}</p>
                    <p class="stat-label">{{ $label }}</p>
                </div>
            </article>
        @endforeach
    </div>

    <div class="grid site-module-grid">
        @foreach ($siteModules as $module)
            <article class="card site-module-card">
                <div class="card-header">
                    <h2 class="card-title">{{ $module['label'] }}</h2>
                    <a class="btn btn-secondary" href="{{ $module['public_url'] }}" target="_blank">Voir site</a>
                </div>
                <div class="card-body">
                    <p class="muted">{{ $module['description'] }}</p>
                    <div class="site-module-actions">
                        @foreach ($module['actions'] as $action)
                            <a class="btn btn-muted" href="{{ route($action['route'], $action['params'] ?? []) }}">{{ $action['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); margin-top: 24px;">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Dernieres inscriptions</h2>
                <a class="btn btn-muted" href="{{ route('admin.admissions.index') }}">Voir</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Numero</th><th>Etudiant</th><th>Etat</th></tr></thead>
                    <tbody>
                    @forelse ($latestApplications as $application)
                        <tr>
                            <td>{{ $application->application_number }}</td>
                            <td>{{ $application->first_name }} {{ $application->last_name }}</td>
                            <td><span class="badge">{{ $application->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">Aucune inscription.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Actualites recentes</h2>
                <a class="btn btn-primary" href="{{ route('admin.news.create') }}">Publier</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Titre</th><th>Categorie</th></tr></thead>
                    <tbody>
                    @forelse ($latestNews as $post)
                        <tr><td>{{ $post->title }}</td><td>{{ $post->category }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">Aucune actualite.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
