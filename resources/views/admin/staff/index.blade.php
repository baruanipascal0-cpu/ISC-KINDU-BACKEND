@extends('admin.layout')

@section('title', 'Enseignants et staff')
@section('subtitle', 'Personnes a afficher dans Nos enseignants, services et pages institutionnelles')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">{{ $role ?: 'Tous les membres' }}</h2>
                <div class="admin-tabs">
                    <a href="{{ route('admin.staff.index') }}" @class(['active' => ! $role])>Tous</a>
                    @foreach ($roles as $item)
                        <a href="{{ route('admin.staff.index', ['role' => $item]) }}" @class(['active' => $role === $item])>{{ $item }}</a>
                    @endforeach
                </div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.staff.create', ['role' => $role]) }}">Nouveau membre</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nom</th><th>Role</th><th>Departement</th><th>Ordre</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($staffMembers as $member)
                        <tr>
                            <td>
                                <strong>{{ $member->name }}</strong>
                                @if ($member->title)
                                    <div class="muted">{{ $member->title }}</div>
                                @endif
                            </td>
                            <td>{{ $member->role }}</td>
                            <td>{{ $member->department ?: '-' }}</td>
                            <td>{{ $member->sort_order }}</td>
                            <td><span class="badge">{{ $member->is_active ? 'visible' : 'masque' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.staff.edit', $member) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.staff.destroy', $member) }}" onsubmit="return confirm('Supprimer ce membre ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Aucun membre ajoute.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $staffMembers->links() }}</div>
    </section>
@endsection
