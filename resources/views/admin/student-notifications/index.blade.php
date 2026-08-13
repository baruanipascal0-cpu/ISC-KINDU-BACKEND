@extends('admin.layout')

@section('title', 'Notifications etudiants')
@section('subtitle', 'Messages visibles dans le portail etudiant')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Notifications</h2>
                <div class="admin-tabs">
                    <a href="{{ route('admin.student-notifications.index') }}" @class(['active' => ! $type])>Toutes</a>
                    @foreach ($types as $item)
                        <a href="{{ route('admin.student-notifications.index', ['type' => $item]) }}" @class(['active' => $type === $item])>{{ $item }}</a>
                    @endforeach
                </div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.student-notifications.create') }}">Nouvelle notification</a>
        </div>

        <div class="card-body grid">
            @forelse ($notifications as $notification)
                <article class="message-card">
                    <div class="message-card-head">
                        <div>
                            <h3>{{ $notification->title }}</h3>
                            <p class="muted">
                                {{ $notification->user?->name ?? 'Destinataire supprime' }}
                                @if ($notification->user?->studentProfile?->matricule)
                                    - {{ $notification->user->studentProfile->matricule }}
                                @endif
                                - {{ $notification->created_at?->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <span class="badge">{{ $notification->type }}</span>
                    </div>
                    <div class="message-body">{{ $notification->message }}</div>
                    <div class="actions">
                        <a class="btn btn-muted" href="{{ route('admin.student-notifications.edit', $notification) }}">Modifier</a>
                        <form method="post" action="{{ route('admin.student-notifications.destroy', $notification) }}" onsubmit="return confirm('Supprimer cette notification ?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">Supprimer</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="muted">Aucune notification etudiante.</div>
            @endforelse
        </div>

        <div class="card-body pagination">{{ $notifications->links() }}</div>
    </section>
@endsection
