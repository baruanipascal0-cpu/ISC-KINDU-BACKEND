@extends('admin.layout')

@section('title', 'Blocs du site')
@section('subtitle', 'Cartes, accroches, etapes et petits contenus affiches sur les pages')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">{{ $group ? ($knownGroups[$group] ?? $group) : 'Tous les blocs' }}</h2>
                <div class="admin-tabs">
                    <a href="{{ route('admin.content-blocks.index') }}" @class(['active' => ! $group])>Tous</a>
                    @foreach ($groups as $groupName)
                        <a href="{{ route('admin.content-blocks.index', ['group' => $groupName]) }}" @class(['active' => $group === $groupName])>{{ $knownGroups[$groupName] ?? $groupName }}</a>
                    @endforeach
                </div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.content-blocks.create', ['group' => $group]) }}">Nouveau bloc</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Titre</th><th>Groupe</th><th>Lien</th><th>Ordre</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($blocks as $block)
                        <tr>
                            <td>
                                <strong>{{ $block->title ?: $block->key }}</strong>
                                @if ($block->subtitle)
                                    <div class="muted">{{ $block->subtitle }}</div>
                                @endif
                            </td>
                            <td>{{ $knownGroups[$block->block_group] ?? $block->block_group }}</td>
                            <td class="muted">{{ $block->link_url ?: '-' }}</td>
                            <td>{{ $block->sort_order }}</td>
                            <td><span class="badge">{{ $block->is_active ? 'visible' : 'masque' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.content-blocks.edit', $block) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.content-blocks.destroy', $block) }}" onsubmit="return confirm('Supprimer ce bloc ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Aucun bloc pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $blocks->links() }}</div>
    </section>
@endsection
