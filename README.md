# Backend ISC KINDU

API Laravel 13 pour connecter le site ISC KINDU.

## Demarrage local

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

URL locale:

```text
http://127.0.0.1:8000
```

Site public local:

```text
http://127.0.0.1:8000/
http://127.0.0.1:8000/actualites.html
http://127.0.0.1:8000/diplomes.html
```

Espace administrateur:

```text
http://127.0.0.1:8000/admin/login
```

## Comptes locaux initiaux

```text
Admin:    admin@isc-kindu.test / password
Etudiant: etudiant@isc-kindu.test / password
```

## Base de donnees

Le projet garde SQLite en local pour demarrer vite:

```text
database/database.sqlite
```

Pour la production, utiliser MySQL avec `.env.production.example`:

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=isc_kindu
DB_USERNAME=root
DB_PASSWORD=
```

Sur Render avec PostgreSQL, utiliser `DB_CONNECTION=pgsql` et `DATABASE_URL` avec l'URL interne de la base Render. Le backend accepte aussi `DB_URL`, mais `DATABASE_URL` est le nom attendu par le guide Render Laravel/Docker.

Puis lancer:

```bash
php artisan migrate --seed
```

Le guide complet est dans `docs/DEPLOYMENT.md`.

Pour verifier Render apres un deploiement:

```text
https://votre-backend.onrender.com/api/health
```

La reponse doit indiquer `database.status=ok` et `database.admin_exists=true`.

## Routes principales

Contenu public:

```text
GET  /api/site/settings
GET  /api/site/menus
GET  /api/media
GET  /api/home/slides
GET  /api/home/cards
GET  /api/home/statistics
GET  /api/pages
GET  /api/sections
GET  /api/programs
GET  /api/news
GET  /api/news/{slug}
GET  /api/publications
GET  /api/publications/{slug}
GET  /api/events
GET  /api/events/{slug}
```

Les pages HTML principales du site chargent `assets/custom/backend-api.js` et `assets/custom/site-backend.js`.
Ces scripts lisent le contenu publie dans l espace administrateur et remplacent les anciennes cartes statiques du site.

Pages detail publiques:

```text
/actualites/{slug}
/publications/{slug}
/evenements/{slug}
```

Inscription etudiant:

```text
POST /api/auth/register
POST /api/auth/login
GET  /api/auth/me
POST /api/auth/logout
POST /api/inscriptions
GET  /api/inscriptions/current
GET  /api/inscriptions/status
```

Portefeuille etudiant:

```text
GET  /api/student/dashboard
GET  /api/student/payments
POST /api/student/payments/proof
GET  /api/student/documents
POST /api/student/documents
GET  /api/student/comments
POST /api/student/comments
GET  /api/student/notifications
```

Administration:

```text
GET   /admin/login
GET   /admin
GET   /admin/parametres
GET   /admin/sections
GET   /admin/actualites
GET   /admin/publications
GET   /admin/evenements
GET   /admin/inscriptions
GET   /admin/messages
GET   /admin/commentaires-etudiants
GET   /admin/production
GET   /api/admin/overview
GET   /api/admin/users
GET   /api/admin/admissions
PATCH /api/admin/admissions/{application}/status
GET   /api/admin/content
GET   /api/admin/audit
```

## Tests

```bash
php artisan test
```

## Sauvegardes

```powershell
.\scripts\backup-mysql.ps1
```

Sauvegarder aussi `storage/app/public`, car ce dossier contient les images et documents publies depuis l admin.
