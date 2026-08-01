<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion admin | ISC KINDU</title>
    <link rel="stylesheet" href="{{ asset('admin-assets/admin.css') }}">
</head>
<body>
    <main class="login-page">
        <section class="login-card">
            <div class="login-logo">
                <img src="{{ asset('images/site/logo.jpg') }}" alt="ISC KINDU">
                <div>
                    <strong>ISC KINDU</strong>
                    <span>Espace administrateur</span>
                </div>
            </div>
            @if ($errors->any())
                <div class="alert alert-error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
            <form method="post" action="{{ route('admin.login.submit') }}" class="grid">
                @csrf
                <div class="field">
                    <label for="email">Adresse mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <label class="checkbox-row">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Garder la session ouverte
                </label>
                <button class="btn btn-primary" type="submit">Se connecter</button>
                <p class="muted">Compte demo: admin@isc-kindu.test / password</p>
            </form>
        </section>
    </main>
</body>
</html>
