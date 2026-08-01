@extends('admin.layout')

@section('title', 'Messages')
@section('subtitle', 'Messages recus depuis le site')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Messages de contact</h2>
        </div>
        <div class="card-body grid">
            @forelse ($messages as $message)
                <article class="message-card">
                    <div class="message-card-head">
                        <div>
                            <h3>{{ $message->subject ?: 'Message sans sujet' }}</h3>
                            <p class="muted">
                                {{ $message->name }}
                                @if ($message->email)
                                    · {{ $message->email }}
                                @endif
                                @if ($message->phone)
                                    · {{ $message->phone }}
                                @endif
                            </p>
                        </div>
                        <span class="badge">{{ $message->status }}</span>
                    </div>

                    <div class="message-body">
                        {{ $message->message }}
                    </div>

                    <form method="post" action="{{ route('admin.messages.update', $message) }}" class="form-grid message-response-form">
                        @csrf
                        @method('PATCH')
                        <label class="field">
                            <span>Etat</span>
                            <select name="status">
                                @foreach (['new', 'read', 'answered', 'closed'] as $item)
                                    <option value="{{ $item }}" @selected($message->status === $item)>{{ $item }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field full">
                            <span>Reponse de l administration</span>
                            <textarea name="response" placeholder="Ecrire la reponse a garder dans le dossier">{{ $message->response }}</textarea>
                        </label>
                        @if ($message->answered_at)
                            <div class="muted full">Derniere reponse: {{ $message->answered_at->format('d/m/Y H:i') }}</div>
                        @endif
                        <div class="field full">
                            <button class="btn btn-secondary" type="submit">Enregistrer la reponse</button>
                        </div>
                    </form>
                </article>
            @empty
                <div class="muted">Aucun message.</div>
            @endforelse
        </div>
        <div class="card-body pagination">{{ $messages->links() }}</div>
    </section>
@endsection
