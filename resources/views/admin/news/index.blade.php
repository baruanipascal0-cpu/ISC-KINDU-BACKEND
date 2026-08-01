@extends('admin.layout')

@section('title', 'Actualites')
@section('subtitle', 'Actualites affichees sur le site')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Actualites</h2>
            <a class="btn btn-primary" href="{{ route('admin.news.create') }}">Publier une actualite</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Titre</th><th>Categorie</th><th>Date</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>{{ $post->title }}</td>
                            <td>{{ $post->category }}</td>
                            <td>{{ optional($post->published_at)->format('d/m/Y') }}</td>
                            <td><span class="badge">{{ $post->is_published ? 'publiee' : 'brouillon' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.news.edit', $post) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.news.destroy', $post) }}" onsubmit="return confirm('Supprimer cette actualite ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucune actualite.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $posts->links() }}</div>
    </section>
@endsection
