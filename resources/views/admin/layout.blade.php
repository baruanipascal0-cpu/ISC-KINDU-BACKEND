<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Administration') | ISC KINDU</title>
    <link rel="stylesheet" href="{{ secure_asset('admin-assets/admin.css') }}">
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('admin.dashboard') }}">
                <img src="{{ secure_asset('images/site/logo.jpg') }}" alt="ISC KINDU">
                <span>
                    <strong>ISC KINDU</strong>
                    <span>Administration</span>
                </span>
            </a>
            <nav class="nav-list">
                @php
                    $navGroups = config('isc_site.admin_nav', []);
                    $isActive = function (array $item): bool {
                        $patterns = (array) ($item['active'] ?? $item['route']);
                        $matchesRoute = collect($patterns)->contains(fn (string $pattern): bool => request()->routeIs($pattern));

                        if (! $matchesRoute) {
                            return false;
                        }

                        foreach (($item['query'] ?? []) as $key => $expected) {
                            $current = request()->query($key);

                            if (is_array($expected)) {
                                if (! in_array($current, $expected, true)) {
                                    return false;
                                }
                            } elseif ($expected === null) {
                                if ($current !== null) {
                                    return false;
                                }
                            } elseif ($current !== $expected) {
                                return false;
                            }
                        }

                        return true;
                    };
                @endphp

                @foreach ($navGroups as $group)
                    <div class="nav-section-title">{{ $group['label'] }}</div>
                    @foreach ($group['items'] as $item)
                        <a class="nav-link {{ $isActive($item) ? 'active' : '' }}" href="{{ route($item['route'], $item['params'] ?? []) }}">{{ $item['label'] }}</a>
                    @endforeach
                @endforeach
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
