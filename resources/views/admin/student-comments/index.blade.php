@extends('admin.layout')

@section('title', 'Commentaires etudiants')
@section('subtitle', 'Messages envoyes depuis le portail etudiant')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Commentaires</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Etudiant</th><th>Sujet</th><th>Message</th><th>Reponse</th><th>Etat</th></tr></thead>
                <tbody>
                    @forelse ($comments as $comment)
                        <tr>
                            <td>
                                {{ $comment->user?->name }}
                                <div class="muted">{{ $comment->user?->email }}</div>
                            </td>
                            <td>{{ $comment->subject }}</td>
                            <td>{{ $comment->message }}</td>
                            <td>
                                <form method="post" action="{{ route('admin.student-comments.update', $comment) }}" class="grid" style="min-width: 260px;">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="response" placeholder="Reponse">{{ $comment->response }}</textarea>
                                    <select name="status">
                                        @foreach (['open', 'answered', 'closed'] as $item)
                                            <option value="{{ $item }}" @selected($comment->status === $item)>{{ $item }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-secondary" type="submit">Repondre</button>
                                </form>
                            </td>
                            <td><span class="badge">{{ $comment->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucun commentaire etudiant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $comments->links() }}</div>
    </section>
@endsection
