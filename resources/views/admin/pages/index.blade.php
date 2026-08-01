@extends('admin.layout')

@section('title', 'Pages du site')
@section('subtitle', 'Accueil, diplome, inscriptions, ressources, alumni et autres pages')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Pages</h2>
            <a class="btn btn-primary" href="{{ route('admin.pages.create') }}">Nouvelle page</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Titre</th><th>Slug</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td>{{ $page->title }}</td>
                            <td>{{ $page->slug }}</td>
                            <td><span class="badge">{{ $page->is_published ? 'publiee' : 'brouillon' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.pages.edit', $page) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Supprimer cette page ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Aucune page.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $pages->links() }}</div>
    </section>
@endsection
