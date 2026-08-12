@extends('admin.layout')

@section('title', 'Medias')
@section('subtitle', 'Photos, videos et fichiers visibles dans la galerie et les blocs du site')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">{{ $collection ?: 'Tous les medias' }}</h2>
                <div class="admin-tabs">
                    <a href="{{ route('admin.media.index') }}" @class(['active' => ! $collection])>Tous</a>
                    @foreach ($collections as $item)
                        <a href="{{ route('admin.media.index', ['collection' => $item]) }}" @class(['active' => $collection === $item])>{{ $item }}</a>
                    @endforeach
                </div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.media.create', ['collection' => $collection]) }}">Nouveau media</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nom</th><th>Collection</th><th>Type</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($mediaFiles as $media)
                        <tr>
                            <td>
                                <strong>{{ $media->name }}</strong>
                                <div class="muted">{{ $media->caption ?: $media->path }}</div>
                            </td>
                            <td>{{ $media->collection }}</td>
                            <td>{{ $media->mime_type ?: 'lien' }}</td>
                            <td><span class="badge">{{ $media->is_published ? 'publie' : 'brouillon' }}</span></td>
                            <td>
                                <div class="actions">
                                    @if ($media->path)
                                        <a class="btn btn-secondary" href="{{ $media->path }}" target="_blank">Ouvrir</a>
                                    @endif
                                    <a class="btn btn-muted" href="{{ route('admin.media.edit', $media) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.media.destroy', $media) }}" onsubmit="return confirm('Supprimer ce media ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucun media publie pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $mediaFiles->links() }}</div>
    </section>
@endsection
