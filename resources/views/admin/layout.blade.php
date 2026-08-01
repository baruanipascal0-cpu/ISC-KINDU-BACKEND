<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Administration') | ISC KINDU</title>
    <link rel="stylesheet" href="{{ asset('admin-assets/admin.css') }}">
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('images/site/logo.jpg') }}" alt="ISC KINDU">
                <span>
                    <strong>ISC KINDU</strong>
                    <span>Administration</span>
                </span>
            </a>
            <nav class="nav-list">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Tableau de bord</a>
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">Parametres</a>
                <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="{{ route('admin.pages.index') }}">Pages du site</a>
                <a class="nav-link {{ request()->routeIs('admin.content-blocks.*') ? 'active' : '' }}" href="{{ route('admin.content-blocks.index') }}">Blocs du site</a>
                <a class="nav-link {{ request()->routeIs('admin.sections.*') ? 'active' : '' }}" href="{{ route('admin.sections.index') }}">Sections</a>
                <a class="nav-link {{ request()->routeIs('admin.admissions.*') ? 'active' : '' }}" href="{{ route('admin.admissions.index') }}">Demandes d admission</a>
                <a class="nav-link {{ request()->routeIs('admin.registry.*') ? 'active' : '' }}" href="{{ route('admin.registry.index') }}">Registre des inscriptions</a>
                <a class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">Actualites</a>
                <a class="nav-link {{ request()->routeIs('admin.publications.*') && ! request('type') ? 'active' : '' }}" href="{{ route('admin.publications.index') }}">Publications</a>
                <a class="nav-link {{ request()->routeIs('admin.graduations.*') ? 'active' : '' }}" href="{{ route('admin.graduations.index') }}">Diplomes</a>
                <a class="nav-link {{ request()->routeIs('admin.publications.*') && request('type') === 'Ressource' ? 'active' : '' }}" href="{{ route('admin.publications.index', ['type' => 'Ressource']) }}">Ressources</a>
                <a class="nav-link {{ request()->routeIs('admin.publications.*') && request('type') === 'Alumni' ? 'active' : '' }}" href="{{ route('admin.publications.index', ['type' => 'Alumni']) }}">Alumni</a>
                <a class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}" href="{{ route('admin.events.index') }}">Evenements</a>
                <a class="nav-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" href="{{ route('admin.messages.index') }}">Messages</a>
                <a class="nav-link {{ request()->routeIs('admin.student-comments.*') ? 'active' : '' }}" href="{{ route('admin.student-comments.index') }}">Commentaires etudiants</a>
                <a class="nav-link {{ request()->routeIs('admin.production.*') ? 'active' : '' }}" href="{{ route('admin.production.index') }}">Production</a>
            </nav>
        </aside>
        <main class="admin-main">
            <header class="topbar">
                <div>
                    <h1>@yield('title', 'Administration')</h1>
                    <div class="muted">@yield('subtitle', 'Gestion du site ISC KINDU')</div>
                </div>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn-muted" type="submit">Se deconnecter</button>
                </form>
            </header>
            <section class="content">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                @yield('content')
            </section>
        </main>
    </div>
</body>
</html>
