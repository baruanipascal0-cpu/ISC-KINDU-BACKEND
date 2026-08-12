@extends('admin.layout')

@section('title', 'Navigation')
@section('subtitle', 'Menus et liens rapides consommes par le frontend')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">{{ $location ?: 'Tous les menus' }}</h2>
                <div class="admin-tabs">
                    <a href="{{ route('admin.menu-items.index') }}" @class(['active' => ! $location])>Tous</a>
                    @foreach ($locations as $item)
                        <a href="{{ route('admin.menu-items.index', ['location' => $item]) }}" @class(['active' => $location === $item])>{{ $item }}</a>
                    @endforeach
                </div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.menu-items.create', ['location' => $location]) }}">Nouveau lien</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Libelle</th><th>Menu</th><th>Parent</th><th>URL</th><th>Ordre</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->label }}</td>
                            <td>{{ $item->location }}</td>
                            <td>{{ $item->parent?->label ?: '-' }}</td>
                            <td class="muted">{{ $item->url }}</td>
                            <td>{{ $item->sort_order }}</td>
                            <td><span class="badge">{{ $item->is_active ? 'visible' : 'masque' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.menu-items.edit', $item) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.menu-items.destroy', $item) }}" onsubmit="return confirm('Supprimer ce lien ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Aucun lien de menu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $items->links() }}</div>
    </section>
@endsection
