@extends('admin.layout')

@section('title', 'Documents etudiants')
@section('subtitle', 'Publier les fiches, attestations, recus et fichiers dans le portail etudiant')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Documents publies</h2>
                <div class="admin-tabs">
                    <a href="{{ route('admin.student-documents.index') }}" @class(['active' => ! $status && ! $type])>Tous</a>
                    @foreach ($types as $item)
                        <a href="{{ route('admin.student-documents.index', ['type' => $item]) }}" @class(['active' => $type === $item])>{{ $item }}</a>
                    @endforeach
                </div>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.student-documents.create') }}">Nouveau document</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Etudiant</th>
                        <th>Document</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Fichier</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td>
                                <strong>{{ $document->user?->name }}</strong>
                                <div class="muted">{{ $document->user?->studentProfile?->matricule ?? $document->user?->email }}</div>
                            </td>
                            <td>
                                <strong>{{ $document->name }}</strong>
                                <div class="muted">{{ $document->issued_at?->format('d/m/Y H:i') ?? 'Date non definie' }}</div>
                            </td>
                            <td>{{ $document->type }}</td>
                            <td><span class="badge">{{ $document->status }}</span></td>
                            <td>
                                @if ($document->file_path)
                                    <a class="btn btn-muted" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}" target="_blank">Ouvrir</a>
                                @else
                                    <span class="muted">Aucun fichier</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.student-documents.edit', $document) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.student-documents.destroy', $document) }}" onsubmit="return confirm('Supprimer ce document ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Aucun document etudiant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body pagination">{{ $documents->links() }}</div>
    </section>
@endsection
