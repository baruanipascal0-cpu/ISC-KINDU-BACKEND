@extends('admin.layout')

@section('title', 'Sections et filieres')
@section('subtitle', 'Sections ISC KINDU visibles sur le site et dans le formulaire inscription')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Sections ISC KINDU</h2>
            <a class="btn btn-primary" href="{{ route('admin.sections.create') }}">Nouvelle section</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Section</th><th>Filieres</th><th>Ordre</th><th>Etat</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($sections as $section)
                        <tr>
                            <td>
                                <strong>{{ $section->name }}</strong>
                                <div class="muted">{{ $section->description }}</div>
                            </td>
                            <td>
                                @forelse ($section->programs as $program)
                                    <div>{{ $program->name }} <span class="muted">({{ $program->cycle }})</span></div>
                                @empty
                                    <span class="muted">Aucune filiere.</span>
                                @endforelse
                            </td>
                            <td>{{ $section->sort_order }}</td>
                            <td><span class="badge">{{ $section->is_active ? 'active' : 'masquee' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-muted" href="{{ route('admin.sections.edit', $section) }}">Modifier</a>
                                    <form method="post" action="{{ route('admin.sections.destroy', $section) }}" onsubmit="return confirm('Supprimer cette section ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucune section.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination">{{ $sections->links() }}</div>
    </section>
@endsection
