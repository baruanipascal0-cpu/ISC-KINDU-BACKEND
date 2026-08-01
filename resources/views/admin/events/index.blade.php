@extends('admin.layout')

@section('title', 'Evenements')
@section('subtitle', 'Agenda et activites publies sur le site')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Evenements</h2>
            <a class="btn btn-primary" href="{{ route('admin.events.create') }}">Nouvel evenement</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Titre</th><th>Lieu</th><th>Date</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->location }}</td>
                            <td>{{ optional($event->starts_at)->format('d/m/Y H:i') }}</td>
                            <td><span class="badge">{{ $event->is_published ? 'publie' : 'brouillon' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.events.edit', $event) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Supprimer cet evenement ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucun evenement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $events->links() }}</div>
    </section>
@endsection
