@extends('admin.layout')

@section('title', 'Bannieres accueil')
@section('subtitle', 'Images et messages du diaporama de la page d accueil')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Diaporama public</h2>
                <div class="muted">Ces bannieres alimentent directement l API <code>/api/home/slides</code>.</div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.banners.create') }}">Nouvelle banniere</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Titre</th>
                        <th>Lien</th>
                        <th>Ordre</th>
                        <th>Etat</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($banners as $banner)
                        <tr>
                            <td>
                                @if ($banner->image_url)
                                    <img src="{{ $banner->image_url }}" alt="" style="width:88px;height:50px;object-fit:cover;border-radius:6px;background:#eef2f7;">
                                @else
                                    <span class="muted">Aucune image</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $banner->title }}</strong>
                                @if ($banner->subtitle)
                                    <div class="muted">{{ $banner->subtitle }}</div>
                                @endif
                            </td>
                            <td class="muted">{{ $banner->link_url ?: '-' }}</td>
                            <td>{{ $banner->sort_order }}</td>
                            <td><span class="badge">{{ $banner->is_active ? 'visible' : 'masquee' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.banners.edit', $banner) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Supprimer cette banniere ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Aucune banniere pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $banners->links() }}</div>
    </section>
@endsection
