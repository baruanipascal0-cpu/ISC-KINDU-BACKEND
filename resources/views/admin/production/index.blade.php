@extends('admin.layout')

@section('title', 'Production')
@section('subtitle', 'Preparation MySQL, stockage, sauvegardes et mise en ligne')

@section('content')
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
        @foreach ($checks as $group => $items)
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ $group }}</h2>
                </div>
                <div class="card-body">
                    <dl class="check-list">
                        @foreach ($items as $label => $value)
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>
        @endforeach
    </div>

    <section class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2 class="card-title">Checklist avant mise en ligne</h2>
        </div>
        <div class="card-body">
            <ol class="deployment-list">
                <li>Creer une base MySQL vide chez l hebergeur.</li>
                <li>Copier `.env.production.example` vers `.env` sur le serveur et remplir les identifiants.</li>
                <li>Lancer les migrations et le seeder initial.</li>
                <li>Creer le lien de stockage public pour les images et documents.</li>
                <li>Programmer une sauvegarde quotidienne de la base et du dossier `storage/app/public`.</li>
                <li>Verifier que l admin est protege avec un mot de passe fort.</li>
            </ol>
        </div>
    </section>
@endsection
