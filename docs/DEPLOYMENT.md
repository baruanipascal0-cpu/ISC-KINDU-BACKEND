# Mise en ligne ISC KINDU

## 1. Base MySQL

Creer une base MySQL chez l hebergeur, par exemple:

```text
DB_DATABASE=isc_kindu
DB_USERNAME=isc_kindu_user
DB_PASSWORD=mot_de_passe_fort
```

Copier `.env.production.example` vers `.env`, remplir `APP_URL`, `FRONTEND_PATH` et les identifiants MySQL.

## 2. Commandes serveur

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 3. Dossiers a sauvegarder

```text
Base MySQL
storage/app/public
.env
```

## 4. Sauvegarde

Sur Windows, utiliser `scripts/backup-mysql.ps1`. Sur Linux, programmer un `mysqldump` quotidien et une copie du dossier `storage/app/public`.

## 5. Verification

Verifier ces URLs apres mise en ligne:

```text
/
/actualites.html
/articles.html
/inscriptions.html
/admin/login
/api/site/settings
```

## 6. Deploiement Render avec Docker

Le backend contient un `Dockerfile` pret pour Render. Creer un nouveau **Web Service** depuis le depot GitHub du backend, choisir l'environnement **Docker**, puis definir ces variables:

```text
APP_NAME=ISC KINDU
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://votre-backend.onrender.com
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://...
DB_SSLMODE=require
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
PUBLIC_STORAGE_PATH=/var/data/storage
PUBLIC_STORAGE_URL=https://votre-backend.onrender.com/storage
LOG_CHANNEL=stderr
RUN_MIGRATIONS=true
RUN_SEEDERS=true
ADMIN_EMAIL=admin@isc-kindu.test
ADMIN_PASSWORD=mot-de-passe-admin-fort
CORS_ALLOWED_ORIGINS=https://isc-kindu-frontend.baruanipascal0.workers.dev
```

Pour MySQL externe, utiliser plutot:

```text
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Le backend accepte aussi `DB_URL`, mais Render documente `DATABASE_URL` pour Laravel/Docker. Si vous liez un **Environment Group**, verifier que le service ne garde pas d'anciennes variables individuelles comme `DB_CONNECTION=mysql`, `DB_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` ou `DB_PASSWORD`: les variables definies directement sur le service ont priorite sur celles du groupe.

Quand vous utilisez l'URL interne Render PostgreSQL, placer le service web et la base dans la meme region Render. Dans un projet Render avec environnements proteges, la base et le service doivent aussi etre dans le meme environnement pour que le reseau prive fonctionne.

Render fournit automatiquement la variable `PORT`; le script `scripts/render-start.sh` configure Apache pour l'utiliser.

Apres le premier deploiement, verifier:

```text
/up
/api/health
/api/site/settings
/api/news
/admin/login
```

`/api/health` doit retourner `database.status=ok` et `database.admin_exists=true`. Si `admin_exists=false`, verifier `RUN_SEEDERS=true`, `ADMIN_EMAIL` et `ADMIN_PASSWORD`, puis redeployer.

Les fichiers envoyes depuis l'administration sont stockes dans le disque public Laravel. Sur Render, le systeme de fichiers est ephemere sans disque persistant: les uploads peuvent disparaitre apres un redeploiement ou un redemarrage.

Pour conserver les photos et documents, ajouter un disque persistant au service backend avec le chemin de montage `/var/data`, puis garder:

```text
FILESYSTEM_DISK=public
MEDIA_DISK=public
PUBLIC_STORAGE_PATH=/var/data/storage
PUBLIC_STORAGE_URL=https://votre-backend.onrender.com/storage
```

Pour un stockage externe persistant, configurer Cloudflare R2 et remplacer `MEDIA_DISK=public` par:

```text
MEDIA_DISK=r2
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_BUCKET=isc-kindu-media
R2_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://media.votre-domaine.tld
R2_REGION=auto
```
